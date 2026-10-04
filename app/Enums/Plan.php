<?php

namespace App\Enums;

enum Plan: string
{
    case Basic = 'basic';
    case Pro = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Pro => 'Pro',
        };
    }

    public function maxStaff(): ?int
    {
        return match ($this) {
            self::Basic => 3,
            self::Pro => null,
        };
    }

    public function allowsAssistant(): bool
    {
        return $this === self::Pro;
    }

    public function stripePriceId(): string
    {
        return (string) config('billing.prices.'.$this->value);
    }

    public static function fromStripePrice(?string $price): self
    {
        foreach (self::cases() as $case) {
            if ($price !== null && $price !== '' && $price === $case->stripePriceId()) {
                return $case;
            }
        }

        return self::Basic;
    }
}
