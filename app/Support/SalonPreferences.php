<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Number;
use Throwable;

final class SalonPreferences
{
    /**
     * @var array<string, string>
     */
    public const LOCALES = [
        'en' => 'English',
        'zh_CN' => '简体中文',
    ];

    /**
     * @var list<string>
     */
    public const CURRENCIES = [
        'SGD',
        'CNY',
        'USD',
        'EUR',
        'GBP',
        'HKD',
        'MYR',
        'JPY',
        'AUD',
    ];

    /**
     * @var list<string>
     */
    public const TIMEZONES = [
        'Asia/Singapore',
        'Asia/Shanghai',
        'Asia/Hong_Kong',
        'Asia/Tokyo',
        'Asia/Kuala_Lumpur',
        'Asia/Bangkok',
        'Asia/Jakarta',
        'Asia/Dubai',
        'Europe/London',
        'Europe/Paris',
        'America/New_York',
        'America/Los_Angeles',
        'Australia/Sydney',
        'UTC',
    ];

    /**
     * @param  list<string>  $values
     * @return array<string, string>
     */
    public static function options(array $values): array
    {
        return array_combine($values, $values);
    }

    public static function apply(Tenant $tenant): void
    {
        app()->setLocale(self::locale($tenant));
    }

    public static function locale(Tenant $tenant): string
    {
        $locale = (string) $tenant->locale;

        return array_key_exists($locale, self::LOCALES) ? $locale : 'en';
    }

    public static function money(int $cents, string $currency, string $locale): string
    {
        $locale = array_key_exists($locale, self::LOCALES) ? $locale : 'en';

        try {
            return Number::currency($cents / 100, $currency, $locale);
        } catch (Throwable) {
            return $currency.' '.number_format($cents / 100, 2);
        }
    }
}
