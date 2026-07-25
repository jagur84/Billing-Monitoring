<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Overwrite config() values with DB-backed settings when present, so every existing
     * config('tripay.x') / config('mail.x') / config('billing.x') call site keeps working
     * unmodified — a saved Setting simply wins over the .env-sourced default.
     */
    public function boot(): void
    {
        if (! $this->settingsTableExists()) {
            return;
        }

        $this->overrideApp();
        $this->overrideMail();
        $this->overrideTripay();
        $this->overrideGenieAcs();
        $this->overrideBilling();
    }

    private function overrideApp(): void
    {
        $name = Setting::get('app_name');
        if ($name !== null && $name !== '') {
            Config::set('app.name', $name);
        }

        $locale = Setting::get('app_locale');
        if (in_array($locale, ['id', 'en'], true)) {
            App::setLocale($locale);
        }
    }

    private function settingsTableExists(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }

    private function overrideMail(): void
    {
        $map = [
            'mail_mailer' => 'mail.default',
            'mail_host' => 'mail.mailers.smtp.host',
            'mail_port' => 'mail.mailers.smtp.port',
            'mail_username' => 'mail.mailers.smtp.username',
            'mail_password' => 'mail.mailers.smtp.password',
            'mail_from_address' => 'mail.from.address',
            'mail_from_name' => 'mail.from.name',
        ];

        foreach ($map as $settingKey => $configKey) {
            $value = Setting::get($settingKey);
            if ($value !== null && $value !== '') {
                Config::set($configKey, $value);
            }
        }

        $encryption = Setting::get('mail_encryption');
        if ($encryption === 'ssl') {
            Config::set('mail.mailers.smtp.scheme', 'smtps');
        } elseif ($encryption === 'tls') {
            Config::set('mail.mailers.smtp.scheme', 'smtp');
        }
    }

    private function overrideTripay(): void
    {
        $mode = Setting::get('tripay_mode');
        if ($mode) {
            Config::set('tripay.mode', $mode);
            Config::set('tripay.base_url', $mode === 'production'
                ? 'https://tripay.co.id/api'
                : 'https://tripay.co.id/api-sandbox');
        }

        foreach (['tripay_merchant_code' => 'tripay.merchant_code', 'tripay_api_key' => 'tripay.api_key', 'tripay_private_key' => 'tripay.private_key', 'tripay_default_method' => 'tripay.default_method'] as $settingKey => $configKey) {
            $value = Setting::get($settingKey);
            if ($value !== null && $value !== '') {
                Config::set($configKey, $value);
            }
        }
    }

    private function overrideGenieAcs(): void
    {
        foreach (['genieacs_base_url' => 'genieacs.base_url', 'genieacs_username' => 'genieacs.username', 'genieacs_password' => 'genieacs.password'] as $settingKey => $configKey) {
            $value = Setting::get($settingKey);
            if ($value !== null && $value !== '') {
                Config::set($configKey, $value);
            }
        }
    }

    private function overrideBilling(): void
    {
        foreach (['invoice_generate_days_before' => 'billing.invoice_generate_days_before', 'isolation_grace_days' => 'billing.isolation_grace_days'] as $settingKey => $configKey) {
            $value = Setting::get($settingKey);
            if ($value !== null && $value !== '') {
                Config::set($configKey, (int) $value);
            }
        }

        $emailOffsets = [];
        foreach (['h-3' => 3, 'h0' => 0, 'h+3' => -3, 'h+7' => -7] as $stage => $defaultDay) {
            $day = Setting::get("reminder_offset_email_{$stage}");
            $emailOffsets[$day !== null ? (int) $day : $defaultDay] = "reminder_{$stage}";
        }
        Config::set('billing.reminder_offsets.email', $emailOffsets);

        $waOffsets = [];
        foreach (['h0' => 0, 'h+3' => -3, 'h+7' => -7] as $stage => $defaultDay) {
            $day = Setting::get("reminder_offset_whatsapp_{$stage}");
            $waOffsets[$day !== null ? (int) $day : $defaultDay] = "reminder_{$stage}";
        }
        Config::set('billing.reminder_offsets.whatsapp', $waOffsets);
    }
}
