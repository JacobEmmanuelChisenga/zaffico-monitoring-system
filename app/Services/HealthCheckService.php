<?php

namespace App\Services;

use App\Models\Applications;
use App\Models\MonitoringCheck;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthCheckService
{
    /**
     * Execute a health check on an application
     *
     * @param Applications $application
     * @return array Result containing status, response_time, status_code, error_message
     */
    public function executeCheck(Applications $application): array
    {
        try {
            $protocol = strtolower($application->protocol ?? 'http');

            return match ($protocol) {
                'http', 'https' => $this->checkHttp($application),
                'tcp' => $this->checkTcp($application),
                'icmp', 'ping' => $this->checkIcmp($application),
                default => $this->checkHttp($application),
            };
        } catch (\Exception $e) {
            Log::error("Health check failed for application {$application->id}: {$e->getMessage()}");

            return [
                'status' => 'down',
                'response_time' => 0,
                'status_code' => null,
                'error_message' => $e->getMessage(),
                'response_body' => null,
            ];
        }
    }

    /**
     * Perform HTTP/HTTPS check
     */
    private function checkHttp(Applications $application): array
    {
        $startTime = microtime(true);
        $protocol = strtolower($application->protocol ?? 'http');
        $url = $this->buildUrl($application, $protocol);

        try {
            $httpClient = Http::timeout($application->timeout ?? 10)
                ->connectTimeout($application->timeout ?? 10);
            
            // Disable SSL verification for HTTPS connections (needed for internal IPs with self-signed certificates)
            if ($protocol === 'https') {
                $httpClient = $httpClient->withoutVerifying();
            }
            
            $response = $httpClient->get($url);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();

            // If we got ANY HTTP response, the server is up and responding
            // Only mark as down if it's a server error (5xx) - but even then, server is responding
            // For monitoring purposes, if server responds, it's "up" (even if with errors)
            // Connection failures/timeouts are the real "down" scenarios
            $isUp = true; // Any HTTP response means server is up and reachable

            return [
                'status' => $isUp ? 'up' : 'down',
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'error_message' => $response->successful() ? null : "HTTP {$statusCode}",
                'response_body' => $response->body(),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Connection timeout, connection refused, DNS failure, or network unreachable
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            // Extract meaningful error message
            $errorMsg = $e->getMessage();
            $host = $application->ip_address;
            $port = $application->port ?? ($protocol === 'https' ? 443 : 80);
            
            // Provide more specific error messages with troubleshooting hints
            if (str_contains($errorMsg, 'Failed to connect')) {
                $errorMsg = "Network connection failed: Cannot connect to {$host}:{$port}. " .
                           "Possible causes: Server is down, port {$port} is blocked by firewall, or network routing issue. " .
                           "Verify: 1) Server is running, 2) Port {$port} is open, 3) IP address is correct, 4) Network connectivity from monitoring server.";
            } elseif (str_contains($errorMsg, 'Connection timed out')) {
                $errorMsg = "Connection timeout: Server at {$host}:{$port} did not respond within " . 
                           ($application->timeout ?? 10) . " seconds. " .
                           "Possible causes: Server is slow, network congestion, or firewall blocking. " .
                           "Consider increasing timeout value if server is known to be slow.";
            } elseif (str_contains($errorMsg, 'Name resolution failed')) {
                $errorMsg = "DNS resolution failed: Cannot resolve hostname '{$host}'. " .
                           "Verify the IP address or hostname is correct.";
            } elseif (str_contains($errorMsg, 'Network is unreachable') || str_contains($errorMsg, 'No route to host')) {
                $errorMsg = "Network unreachable: No route to {$host}:{$port}. " .
                           "Possible causes: Network routing issue, firewall blocking, or server is on a different network segment.";
            } else {
                $errorMsg = 'Connection failed: ' . $errorMsg;
            }
            
            // Log network failure for debugging
            Log::warning("Network failure detected for {$application->name} ({$url}): {$errorMsg}");
            
            return [
                'status' => 'down',
                'response_time' => $responseTime,
                'status_code' => null,
                'error_message' => $errorMsg,
                'response_body' => null,
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // HTTP request exception (but we got a response, so server is up)
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $statusCode = $e->response ? $e->response->status() : null;
            
            return [
                'status' => 'up', // Server responded, so it's up
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'error_message' => $statusCode ? "HTTP {$statusCode}" : $e->getMessage(),
                'response_body' => $e->response ? $e->response->body() : null,
            ];
        } catch (\Exception $e) {
            // Any other exception (DNS failure, network error, etc.) = down
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            // Check if it's a network-related error
            $errorMsg = $e->getMessage();
            $isNetworkError = str_contains($errorMsg, 'resolve') ||
                             str_contains($errorMsg, 'timeout') ||
                             str_contains($errorMsg, 'connection') ||
                             str_contains($errorMsg, 'network') ||
                             str_contains($errorMsg, 'unreachable') ||
                             str_contains($errorMsg, 'No route');

            return [
                'status' => 'down',
                'response_time' => $responseTime,
                'status_code' => null,
                'error_message' => $isNetworkError ? 'Network error: ' . $errorMsg : $errorMsg,
                'response_body' => null,
            ];
        }
    }

    /**
     * Perform TCP connection check
     */
    private function checkTcp(Applications $application): array
    {
        $startTime = microtime(true);
        $host = $application->ip_address;
        $port = $application->port ?? 80;
        $timeout = $application->timeout ?? 10;

        try {
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

            if ($socket) {
                fclose($socket);
                $responseTime = round((microtime(true) - $startTime) * 1000);

                return [
                    'status' => 'up',
                    'response_time' => $responseTime,
                    'status_code' => 200,
                    'error_message' => null,
                    'response_body' => "TCP connection successful on port {$port}",
                ];
            } else {
                $responseTime = round((microtime(true) - $startTime) * 1000);
                
                // Determine if it's a network error
                $errorMsg = $errstr ?: "Connection failed (Error code: {$errno})";
                $isNetworkError = in_array($errno, [10060, 10061, 11001, 11002, 11003, 11004]) || // Windows network errors
                                 str_contains(strtolower($errorMsg), 'timeout') ||
                                 str_contains(strtolower($errorMsg), 'refused') ||
                                 str_contains(strtolower($errorMsg), 'unreachable') ||
                                 str_contains(strtolower($errorMsg), 'no route');

                return [
                    'status' => 'down',
                    'response_time' => $responseTime,
                    'status_code' => null,
                    'error_message' => $isNetworkError ? "Network error: {$errorMsg}" : "TCP connection failed: {$errorMsg}",
                    'response_body' => null,
                ];
            }
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $errorMsg = $e->getMessage();
            $isNetworkError = str_contains(strtolower($errorMsg), 'timeout') ||
                             str_contains(strtolower($errorMsg), 'connection') ||
                             str_contains(strtolower($errorMsg), 'network') ||
                             str_contains(strtolower($errorMsg), 'unreachable');

            return [
                'status' => 'down',
                'response_time' => $responseTime,
                'status_code' => null,
                'error_message' => $isNetworkError ? "Network error: {$errorMsg}" : $errorMsg,
                'response_body' => null,
            ];
        }
    }

    /**
     * Perform ICMP/Ping check
     */
    private function checkIcmp(Applications $application): array
    {
        $host = $application->ip_address;
        $timeout = $application->timeout ?? 10;
        $startTime = microtime(true);

        try {
            // Platform-specific ping command
            $command = $this->getPingCommand($host, $timeout);
            $output = null;
            $exitCode = null;

            exec($command, $output, $exitCode);

            $responseTime = round((microtime(true) - $startTime) * 1000);

            if ($exitCode === 0) {
                return [
                    'status' => 'up',
                    'response_time' => $responseTime,
                    'status_code' => 200,
                    'error_message' => null,
                    'response_body' => implode("\n", $output),
                ];
            } else {
                return [
                    'status' => 'down',
                    'response_time' => $responseTime,
                    'status_code' => null,
                    'error_message' => "Ping failed: no response from host",
                    'response_body' => null,
                ];
            }
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);

            return [
                'status' => 'down',
                'response_time' => $responseTime,
                'status_code' => null,
                'error_message' => $e->getMessage(),
                'response_body' => null,
            ];
        }
    }

    /**
     * Build the full URL for HTTP/HTTPS checks
     */
    private function buildUrl(Applications $application, string $protocol): string
    {
        $host = $application->ip_address;
        // Use default ports if not specified
        $defaultPorts = ['http' => 80, 'https' => 443];
        $port = $application->port ?? $defaultPorts[$protocol] ?? 80;
        $portString = in_array($port, [80, 443]) ? '' : ":{$port}";
        $endpoint = $application->endpoint ? ltrim($application->endpoint, '/') : '';
        $endpointString = $endpoint ? "/{$endpoint}" : '';

        return "{$protocol}://{$host}{$portString}{$endpointString}";
    }

    /**
     * Get platform-specific ping command
     */
    private function getPingCommand(string $host, int $timeout): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            return "ping -n 1 -w " . ($timeout * 1000) . " {$host}";
        } else {
            // Linux/Mac
            return "ping -c 1 -W {$timeout} {$host} 2>&1";
        }
    }

    /**
     * Record a monitoring check result
     */
    public function recordCheck(Applications $application, array $checkResult): MonitoringCheck
    {
        // Optimize storage: only store response_body for failed checks or if explicitly enabled
        $storeResponseBody = config('monitoring.storage.store_response_body_on_success', false);
        $responseBody = null;

        if ($checkResult['response_body']) {
            // Only store response_body if:
            // 1. Check failed (status is not 'up'), OR
            // 2. Storage optimization is disabled (store all responses)
            if ($checkResult['status'] !== 'up' || $storeResponseBody) {
                $maxSize = config('monitoring.storage.max_response_body_size', 10000);
                $responseBody = $checkResult['response_body'];
                
                // Truncate if too large
                if (strlen($responseBody) > $maxSize) {
                    $responseBody = substr($responseBody, 0, $maxSize) . '... [truncated]';
                }
            }
        }

        return MonitoringCheck::create([
            'application_id' => $application->id,
            'status' => $checkResult['status'],
            'response_time' => $checkResult['response_time'],
            'status_code' => $checkResult['status_code'],
            'error_message' => $checkResult['error_message'],
            'response_body' => $responseBody, // Optimized: null for successful checks
            'checked_at' => now(),
        ]);
    }

    /**
     * Execute check and record result
     */
    public function executeAndRecord(Applications $application): MonitoringCheck
    {
        $checkResult = $this->executeCheck($application);
        return $this->recordCheck($application, $checkResult);
    }
}
