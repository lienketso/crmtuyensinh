<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
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
        // Nạp một số cấu hình động từ bảng settings (nếu đã migrate)
        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            if (Schema::hasTable('settings')) {
                $settings = Setting::query()->pluck('value', 'key');

                $get = function (string $key, mixed $default = null) use ($settings) {
                    return $settings[$key] ?? $default;
                };

                // Tên site
                if ($name = $get('site.name')) {
                    config(['app.name' => $name]);
                }

                // Logo / favicon (dùng trong Blade hoặc frontend)
                if ($logo = $get('site.logo')) {
                    config(['app.crm_logo' => $logo]);
                }
                if ($favicon = $get('site.favicon')) {
                    config(['app.crm_favicon' => $favicon]);
                }

                // Mail
                $mailConfig = [];

                if ($default = $get('mail.default')) {
                    $mailConfig['mail.default'] = $default;
                }
                if ($fromName = $get('mail.from.name')) {
                    $mailConfig['mail.from.name'] = $fromName;
                }
                if ($fromAddress = $get('mail.from.address')) {
                    $mailConfig['mail.from.address'] = $fromAddress;
                }
                if ($leadRecipient = $get('mail.lead_recipient')) {
                    $mailConfig['mail.lead_recipient'] = $leadRecipient;
                }

                $smtp = [];
                if ($host = $get('mail.smtp.host')) {
                    $smtp['host'] = $host;
                }
                if ($port = $get('mail.smtp.port')) {
                    $smtp['port'] = (int) $port;
                }
                if ($scheme = $get('mail.smtp.scheme')) {
                    $smtp['scheme'] = $scheme;
                }
                if ($username = $get('mail.smtp.username')) {
                    $smtp['username'] = $username;
                }
                if ($password = $get('mail.smtp.password')) {
                    $smtp['password'] = $password;
                }

                if (! empty($smtp)) {
                    $mailConfig['mail.mailers.smtp'] = array_merge(
                        config('mail.mailers.smtp', []),
                        $smtp
                    );
                }

                if (! empty($mailConfig)) {
                    config($mailConfig);
                }
            }
        }
    }
}
