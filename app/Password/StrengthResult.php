<?php

declare(strict_types=1);

namespace App\Password;

use App\Password\Rules\PasswordRule;

/**
 * Immutable outcome of checking a password against the configured rules.
 */
final readonly class StrengthResult
{
    /**
     * @param list<PasswordRule> $failedRules rules the password did not satisfy
     */
    public function __construct(
        public PasswordStrength $strength,
        public int $score,
        public array $failedRules,
    ) {
    }

    /**
     * @return array{strength: string, score: int, failed_rules: list<array{name: string, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'strength' => $this->strength->value,
            'score' => $this->score,
            'failed_rules' => array_map(
                static fn (PasswordRule $rule): array => [
                    'name' => $rule->name(),
                    'message' => $rule->message(),
                ],
                $this->failedRules,
            ),
        ];
    }
}
