<?php

declare(strict_types=1);

namespace App\Core\Settings;

use App\Core\Settings\Models\SettingRecord;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SettingManager
{
    /**
     * @var array<string, mixed>
     */
    private array $cache = [];

    private bool $loaded = false;

    /**
     * Get a setting by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureLoaded();

        return $this->cache[$key] ?? $default;
    }

    /**
     * Determine if a setting exists.
     */
    public function has(string $key): bool
    {
        $this->ensureLoaded();

        return array_key_exists($key, $this->cache);
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general', bool $encrypt = false): void
    {
        /** @var SettingRecord $record */
        $record = SettingRecord::firstOrNew(['key' => $key]);
        $record->group = $group;
        $record->setCastValue($value, $type, $encrypt);
        $record->save();

        $this->cache[$key] = $record->getCastValue();
    }

    /**
     * Get all cached settings.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->ensureLoaded();

        return $this->cache;
    }

    /**
     * Refresh settings cache from database.
     */
    public function refresh(): void
    {
        $this->loaded = false;
        $this->cache = [];
        $this->ensureLoaded();
    }

    /**
     * Apply runtime configuration overrides (app name, SMTP, locale, timezone).
     */
    public function loadAndApplyConfigOverrides(): void
    {
        try {
            if (! Schema::hasTable('cms_settings')) {
                return;
            }

            $this->ensureLoaded();

            if (! empty($this->cache['site_name'])) {
                Config::set('app.name', (string) $this->cache['site_name']);
            }

            if (! empty($this->cache['site_timezone'])) {
                Config::set('app.timezone', (string) $this->cache['site_timezone']);
                date_default_timezone_set((string) $this->cache['site_timezone']);
            }

            if (! empty($this->cache['site_locale'])) {
                app()->setLocale((string) $this->cache['site_locale']);
            }

            // SMTP overrides
            if (! empty($this->cache['mail_host'])) {
                Config::set('mail.mailers.smtp.host', (string) $this->cache['mail_host']);
            }
            if (! empty($this->cache['mail_port'])) {
                Config::set('mail.mailers.smtp.port', (int) $this->cache['mail_port']);
            }
            if (! empty($this->cache['mail_encryption'])) {
                Config::set('mail.mailers.smtp.encryption', (string) $this->cache['mail_encryption']);
            }
            if (! empty($this->cache['mail_username'])) {
                Config::set('mail.mailers.smtp.username', (string) $this->cache['mail_username']);
            }
            if (! empty($this->cache['mail_password'])) {
                Config::set('mail.mailers.smtp.password', (string) $this->cache['mail_password']);
            }
            if (! empty($this->cache['mail_from_address'])) {
                Config::set('mail.from.address', (string) $this->cache['mail_from_address']);
            }
            if (! empty($this->cache['mail_from_name'])) {
                Config::set('mail.from.name', (string) $this->cache['mail_from_name']);
            }
        } catch (Throwable) {
            // Silently allow bootstrapping during initial install or migrations
        }
    }

    /**
     * Ensure records are loaded into cache.
     */
    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        try {
            if (! Schema::hasTable('cms_settings')) {
                $this->loaded = true;

                return;
            }

            $records = SettingRecord::all();
            foreach ($records as $record) {
                $this->cache[$record->key] = $record->getCastValue();
            }
        } catch (Throwable) {
            // Ignore during early boot / no db
        }

        $this->loaded = true;
    }
}
