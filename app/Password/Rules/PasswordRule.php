<?php

declare(strict_types=1);

namespace App\Password\Rules;

/**
 * A single password requirement (Strategy pattern).
 *
 * Each concrete rule decides whether a password satisfies it and exposes a
 * stable machine name plus a human-readable message for failures.
 */
interface PasswordRule
{
    /**
     * Stable machine-readable identifier, e.g. "min_length".
     */
    public function name(): string;

    /**
     * Polish message shown when the rule is not satisfied.
     */
    public function message(): string;

    public function isSatisfiedBy(string $password): bool;
}
