<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default timezone (can be overridden in .env)
        $timezone = config('app.timezone', 'UTC');
        date_default_timezone_set($timezone);
        Carbon::setLocale('en');
        
        // Blade directive for formatted time with timezone
        Blade::directive('timezone', function ($expression) {
            return "<?php echo \Carbon\Carbon::parse($expression)->format('M d, Y H:i:s') . ' (' . config('app.timezone') . ')'; ?>";
        });
        
        // Blade directive for relative time with absolute time on hover
        Blade::directive('timeWithTooltip', function ($expression) {
            return "<?php 
                \$date = \Carbon\Carbon::parse($expression);
                echo '<span title=\"' . \$date->format('M d, Y H:i:s') . ' (' . config('app.timezone') . ')\">' . \$date->diffForHumans() . '</span>';
            ?>";
        });
    }
}
