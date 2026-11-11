<?php

namespace App\Providers;

use App\Models\EmailSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

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
        $this->configureDynamicMail();
    }

    /**
     * Override mail config with the active EmailSetting record.
     */
    private function configureDynamicMail(): void
    {
        try {
            $activeEmail = EmailSetting::getActive();

            if ($activeEmail) {
                Config::set('mail.mailers.smtp.host', $activeEmail->host);
                Config::set('mail.mailers.smtp.port', $activeEmail->port);
                Config::set('mail.mailers.smtp.encryption', $activeEmail->encryption);
                Config::set('mail.mailers.smtp.username', $activeEmail->username);
                Config::set('mail.mailers.smtp.password', $activeEmail->password);
                Config::set('mail.from.address', $activeEmail->from_address);
                Config::set('mail.from.name', $activeEmail->from_name);
            }
        } catch (\Throwable $e) {
            // Table may not exist yet during migration — silently ignore
        }
    }
}