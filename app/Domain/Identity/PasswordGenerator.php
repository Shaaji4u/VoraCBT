<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use Exception;

class PasswordGenerator
{
    private const LENGTH = 10; // Default length
    private const UPPERCASE = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // Excludes I, O
    private const LOWERCASE = 'abcdefghijkmnpqrstuvwxyz'; // Excludes l, o
    private const NUMBERS = '23456789'; // Excludes 0, 1
    private const SPECIAL = ''; // Not required by prompt, but can be added if needed. Prompt says Uppercase, Lowercase, Number.

    public function generate(int $length = self::LENGTH): string
    {
        if ($length < 8) {
            $length = 8;
        }

        $allChars = self::UPPERCASE . self::LOWERCASE . self::NUMBERS;
        $password = '';

        // Ensure at least one of each required type
        $password .= self::UPPERCASE[random_int(0, strlen(self::UPPERCASE) - 1)];
        $password .= self::LOWERCASE[random_int(0, strlen(self::LOWERCASE) - 1)];
        $password .= self::NUMBERS[random_int(0, strlen(self::NUMBERS) - 1)];

        // Fill the rest
        for ($i = 3; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle
        $passwordArray = str_split($password);
        shuffle($passwordArray);

        return implode('', $passwordArray);
    }
}
