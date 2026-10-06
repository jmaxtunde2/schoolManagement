<?php

namespace App\Services\TwoFactor;

use Illuminate\Support\Facades\Hash;

class RecoveryCodeService
{
    public function generate(int $count = 8): array
    {
        $plain = [];
        $hashes = [];
        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4))).'-'.strtoupper(bin2hex(random_bytes(4)));
            $plain[] = $code;
            $hashes[] = Hash::make($code);
        }

        return ['plain' => $plain, 'hashes' => $hashes];
    }

    public function consume(array $hashes, string $code): array
    {
        foreach ($hashes as $i => $hash) {
            if (Hash::check(strtoupper(trim($code)), $hash)) {
                array_splice($hashes, $i, 1);

                return [true, array_values($hashes)];
            }
        }

        return [false, $hashes];
    }
}
