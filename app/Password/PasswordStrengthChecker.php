<?php

declare(strict_types=1);

namespace App\Password;

use App\Password\Rules\PasswordRule;
use Traversable;

/**
 * Evaluates a password against an injected set of rules.
 *
 * It depends only on the {@see PasswordRule} abstraction, so new rules can be
 * added without changing this class (OCP, DIP).
 */
final class PasswordStrengthChecker
{
    /** @var list<PasswordRule> */
    private array $rules;

    /**
     * @param iterable<PasswordRule> $rules
     */
    public function __construct(iterable $rules)
    {
        $this->rules = $rules instanceof Traversable
            ? array_values(iterator_to_array($rules))
            : array_values($rules);
    }

    public function check(string $password): StrengthResult
    {
        $failedRules = array_values(array_filter(
            $this->rules,
            static fn (PasswordRule $rule): bool => ! $rule->isSatisfiedBy($password),
        ));

        $score = count($this->rules) - count($failedRules);

        return new StrengthResult(PasswordStrength::fromScore($score), $score, $failedRules);
    }
}
