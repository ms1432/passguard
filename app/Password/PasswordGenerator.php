<?php

declare(strict_types=1);

namespace App\Password;

use InvalidArgumentException;
use Random\Randomizer;

/**
 * Generates passwords that always contain at least one lowercase letter,
 * one uppercase letter, one digit and one special character.
 *
 * Randomness is delegated to an injected {@see Randomizer} so tests can supply
 * a seeded engine. It never uses rand(), mt_rand() or str_shuffle().
 */
final class PasswordGenerator
{
    private const int MIN_LENGTH = 8;
    private const int MAX_LENGTH = 64;

    private const string LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const string UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const string DIGITS = '0123456789';
    private const string SPECIAL = '!@#$%^&*()-_=+[]{};:,.?';

    public function __construct(
        private readonly Randomizer $randomizer = new Randomizer(),
    ) {
    }

    public function generate(int $length = 16): string
    {
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Password length must be between %d and %d.',
                self::MIN_LENGTH,
                self::MAX_LENGTH,
            ));
        }

        $characters = $this->requiredCharacters();
        $pool = self::LOWERCASE.self::UPPERCASE.self::DIGITS.self::SPECIAL;

        for ($i = count($characters); $i < $length; $i++) {
            $characters[] = $this->pick($pool);
        }

        return implode('', $this->randomizer->shuffleArray($characters));
    }

    /**
     * One guaranteed character from each required class.
     *
     * @return list<string>
     */
    private function requiredCharacters(): array
    {
        return [
            $this->pick(self::LOWERCASE),
            $this->pick(self::UPPERCASE),
            $this->pick(self::DIGITS),
            $this->pick(self::SPECIAL),
        ];
    }

    private function pick(string $characters): string
    {
        return $characters[$this->randomizer->getInt(0, strlen($characters) - 1)];
    }
}
