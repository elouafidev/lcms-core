<?php

declare(strict_types=1);

namespace App\Core\Security\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

class RecoveryCodeService
{
    /**
     * Generate a list of fresh recovery codes.
     *
     * @return list<string>
     */
    public function generate(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $part1 = strtoupper(Str::random(5));
            $part2 = strtoupper(Str::random(5));
            $codes[] = "{$part1}-{$part2}";
        }

        return $codes;
    }

    /**
     * Encrypt a list of recovery codes for database storage.
     *
     * @param  list<string>  $codes
     */
    public function encryptCodes(array $codes): string
    {
        return Crypt::encryptString((string) json_encode($codes));
    }

    /**
     * Decrypt recovery codes from database storage.
     *
     * @return list<string>
     */
    public function decryptCodes(?string $payload): array
    {
        if ($payload === null || $payload === '') {
            return [];
        }

        try {
            $json = Crypt::decryptString($payload);
            $decoded = json_decode($json, true);

            if (! is_array($decoded)) {
                return [];
            }

            return array_values(array_filter($decoded, 'is_string'));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Verify a recovery code and return remaining codes if valid, or null if invalid.
     *
     * @return list<string>|null
     */
    public function verifyAndConsume(string $enteredCode, ?string $payload): ?array
    {
        $codes = $this->decryptCodes($payload);
        $normalized = strtoupper(trim(str_replace(' ', '', $enteredCode)));

        foreach ($codes as $index => $code) {
            $normalizedStored = strtoupper(trim(str_replace(' ', '', $code)));
            if (hash_equals($normalizedStored, $normalized)) {
                unset($codes[$index]);

                return array_values($codes);
            }
        }

        return null;
    }
}
