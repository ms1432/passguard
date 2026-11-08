<?php

declare(strict_types=1);

namespace App\Providers;

use App\Password\PasswordGenerator;
use App\Password\PasswordStrengthChecker;
use App\Password\Rules\ContainsDigitRule;
use App\Password\Rules\ContainsLowercaseRule;
use App\Password\Rules\ContainsSpecialCharRule;
use App\Password\Rules\ContainsUppercaseRule;
use App\Password\Rules\MinLengthRule;
use Illuminate\Support\ServiceProvider;

final class PasswordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PasswordStrengthChecker::class, static fn (): PasswordStrengthChecker => new PasswordStrengthChecker([
            new MinLengthRule(),
            new ContainsDigitRule(),
            new ContainsLowercaseRule(),
            new ContainsUppercaseRule(),
            new ContainsSpecialCharRule(),
        ]));

        $this->app->singleton(PasswordGenerator::class, static fn (): PasswordGenerator => new PasswordGenerator());
    }
}
