<?php

namespace Yuyu\MailLog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent; 
use Illuminate\Console\Scheduling\Schedule;
use Yuyu\MailLog\Listeners\EmailSending;
use Yuyu\MailLog\Listeners\EmailSent;
use Yuyu\MailLog\Models\EmailLog;

class MailLogServiceProvider extends EventServiceProvider
{
    /**
     * Publishable path
     * 
     * @var string
     */
    private $publishablePath = __DIR__.'/../../publishable';

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        MessageSending::class => [
            EmailSending::class,
        ],
        MessageSent::class => [
            EmailSent::class,
        ]
    ];

    /**
     * Register any other events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        /**
         * Load migration files
         */
        $this->loadMigrationsFrom("{$this->publishablePath}/database/migrations/");

        /**
         * Load route files
         */
        $this->loadRoutesFrom("{$this->publishablePath}/routes/web.php");

        /**
         * Register publishable resources
         */
        if ($this->app->runningInConsole()) {
            $this->registerPublishableResources();
        }

        $this->app->booted(function () {
            $interval = config('mailLog.interval');
            if(!empty($interval) && $interval >= 1) {
                /**
                 * Schedule cron job to update email log for smtp connection error.
                 */
                $schedule = app(Schedule::class);
                $schedule->call(function () use($interval){
                    $emailLog = EmailLog::where('status', config('mailLog.status.pending'))
                        ->whereRaw("created_at < NOW() - INTERVAL {$interval} MINUTE")
                        ->update([
                            'status' => config('mailLog.status.error'),
                            'error' => config('mailLog.errors.INVALID_MAILER_CONNECTION')
                        ]);
                })->cron("*/{$interval} * * * *");
            }
        });
       
    }

    /**
     * Register the publishable files.
     * @return void
     */
    private function registerPublishableResources()
    {
        $publishable = [
            'mail-log-config' => [
                "{$this->publishablePath}/config/" => config_path(),
            ],
        ];

        foreach ($publishable as $group => $paths) {
            $this->publishes($paths, $group);
        }
    }
}
