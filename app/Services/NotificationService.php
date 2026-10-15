<?php

namespace App\Services;

use App\Models\DowntimeIncident;
use App\Models\AlertRule;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notifications when an incident occurs
     */
    public function notifyIncident(DowntimeIncident $incident, ?AlertRule $rule = null): void
    {
        try {
            // Create notification record
            $notification = Notification::create([
                'application_id' => $incident->application_id,
                'incident_id' => $incident->id,
                'type' => 'email', // Default type
                'recipient' => $rule->notification_email ?? config('mail.from.address', 'admin@example.com'),
                'subject' => "🚨 Alert: {$incident->application->name} is DOWN",
                'message' => "Application {$incident->application->name} is down. Started at: {$incident->started_at->format('Y-m-d H:i:s')}. Error: {$incident->error_message}",
                'status' => 'pending',
                'sent_at' => null,
            ]);

            // Send email if configured
            if ($rule && $rule->notification_email) {
                $this->sendEmailNotification($incident, $rule, 'incident');
                $notification->update(['status' => 'sent', 'sent_at' => now()]);
            }

            // Send Slack if configured
            if ($rule && $rule->notification_slack_webhook) {
                $this->sendSlackNotification($incident, $rule, 'incident');
            }

            Log::info("Notifications sent for incident {$incident->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send incident notifications: {$e->getMessage()}");
        }
    }

    /**
     * Send notifications when an incident is resolved
     */
    public function notifyResolution(DowntimeIncident $incident): void
    {
        try {
            $rule = $incident->alertRule ?? AlertRule::where('application_id', $incident->application_id)->first();

            // Create notification even if no rule exists
            $notification = Notification::create([
                'application_id' => $incident->application_id,
                'incident_id' => $incident->id,
                'type' => 'email', // Default type
                'recipient' => $rule->notification_email ?? config('mail.from.address', 'admin@example.com'),
                'subject' => "✅ Resolved: {$incident->application->name} is BACK ONLINE",
                'message' => "Application {$incident->application->name} is back online. Duration: " . ($incident->duration ?? 0) . " minutes",
                'status' => 'pending',
                'sent_at' => null,
            ]);

            if (!$rule) {
                return;
            }

            // Send email if configured
            if ($rule->notification_email) {
                $this->sendEmailNotification($incident, $rule, 'resolution');
                $notification->update(['status' => 'sent', 'sent_at' => now()]);
            }

            // Send Slack if configured
            if ($rule->notification_slack_webhook) {
                $this->sendSlackNotification($incident, $rule, 'resolution');
            }

            Log::info("Resolution notifications sent for incident {$incident->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send resolution notifications: {$e->getMessage()}");
        }
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(DowntimeIncident $incident, AlertRule $rule, string $type): void
    {
        try {
            $emails = explode(',', $rule->notification_email);
            $emails = array_map('trim', $emails);

            $subject = $type === 'incident'
                ? "🚨 Alert: {$incident->application->name} is DOWN"
                : "✅ Resolved: {$incident->application->name} is BACK ONLINE";

            $message = $this->buildEmailMessage($incident, $type);

            foreach ($emails as $email) {
                Mail::raw($message, function ($mail) use ($email, $subject) {
                    $mail->to($email)
                        ->subject($subject)
                        ->from(config('mail.from.address'));
                });
            }

            Log::info("Email notification sent to {$rule->notification_email}");
        } catch (\Exception $e) {
            Log::error("Failed to send email notification: {$e->getMessage()}");
        }
    }

    /**
     * Send Slack notification
     */
    private function sendSlackNotification(DowntimeIncident $incident, AlertRule $rule, string $type): void
    {
        try {
            $color = $type === 'incident' ? '#FF0000' : '#00FF00';
            $status = $type === 'incident' ? '🔴 DOWN' : '🟢 UP';

            $payload = [
                'attachments' => [
                    [
                        'color' => $color,
                        'title' => "{$status} {$incident->application->name}",
                        'fields' => [
                            [
                                'title' => 'Application',
                                'value' => $incident->application->name,
                                'short' => true,
                            ],
                            [
                                'title' => 'Severity',
                                'value' => ucfirst($incident->severity ?? 'high'),
                                'short' => true,
                            ],
                            [
                                'title' => 'Time',
                                'value' => now()->format('Y-m-d H:i:s'),
                                'short' => false,
                            ],
                        ],
                    ],
                ],
            ];

            if ($type === 'incident' && $incident->error_message) {
                $payload['attachments'][0]['fields'][] = [
                    'title' => 'Error',
                    'value' => $incident->error_message,
                    'short' => false,
                ];
            }

            \Illuminate\Support\Facades\Http::post($rule->notification_slack_webhook, $payload);

            Log::info("Slack notification sent");
        } catch (\Exception $e) {
            Log::error("Failed to send Slack notification: {$e->getMessage()}");
        }
    }

    /**
     * Build email message content
     */
    private function buildEmailMessage(DowntimeIncident $incident, string $type): string
    {
        $app = $incident->application->name;

        if ($type === 'incident') {
            $message = "Alert: Application '{$app}' is DOWN\n\n";
            $message .= "Started At: " . $incident->started_at->format('Y-m-d H:i:s') . "\n";
            $message .= "Severity: " . ucfirst($incident->severity ?? 'high') . "\n";
            $message .= "Error: " . ($incident->error_message ?? 'Unknown') . "\n";
        } else {
            $message = "Resolution: Application '{$app}' is BACK ONLINE\n\n";
            $message .= "Started At: " . $incident->started_at->format('Y-m-d H:i:s') . "\n";
            $message .= "Resolved At: " . $incident->resolved_at->format('Y-m-d H:i:s') . "\n";
            $message .= "Duration: " . $this->calculateDuration($incident) . "\n";
        }

        return $message;
    }

    /**
     * Calculate incident duration
     */
    private function calculateDuration(DowntimeIncident $incident): string
    {
        if (!$incident->resolved_at) {
            return 'Ongoing';
        }

        $diff = $incident->resolved_at->diff($incident->started_at);

        $hours = $diff->h + ($diff->d * 24);
        $minutes = $diff->i;

        return "{$hours}h {$minutes}m";
    }
}
