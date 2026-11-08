<?php

declare(strict_types=1);

namespace App\Password;

enum PasswordStrength: string
{
    case Weak = 'weak';
    case Medium = 'medium';
    case Strong = 'strong';

    /**
     * Map a satisfied-rule count (0-5) to a strength level:
     * 0-2 weak, 3-4 medium, 5 strong.
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 5 => self::Strong,
            $score >= 3 => self::Medium,
            default => self::Weak,
        };
    }
}
