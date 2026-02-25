<?php

namespace App\Services\WireGuard;

use RuntimeException;

class WireGuardKeyGenerator
{
    public function generateClientKeypair(): array
    {
        if (function_exists('sodium_crypto_scalarmult_base')) {
            $privateRaw = random_bytes(SODIUM_CRYPTO_BOX_SECRETKEYBYTES);
            $privateRaw[0] = chr(ord($privateRaw[0]) & 248);
            $privateRaw[31] = chr((ord($privateRaw[31]) & 127) | 64);

            $publicRaw = sodium_crypto_scalarmult_base($privateRaw);

            return [
                'private' => base64_encode($privateRaw),
                'public' => base64_encode($publicRaw),
            ];
        }

        if ($this->binaryExists('wg')) {
            $private = trim((string) shell_exec('wg genkey 2>/dev/null'));
            if ($private === '') {
                throw new RuntimeException('Impossible de générer la clé privée WireGuard.');
            }

            $escaped = escapeshellarg($private);
            $public = trim((string) shell_exec("bash -lc 'echo {$escaped} | wg pubkey' 2>/dev/null"));
            if ($public === '') {
                throw new RuntimeException('Impossible de dériver la clé publique WireGuard.');
            }

            return ['private' => $private, 'public' => $public];
        }

        throw new RuntimeException('Aucun générateur de clés WireGuard disponible (ext-sodium ou binaire wg).');
    }

    public function generatePresharedKey(): ?string
    {
        if ($this->binaryExists('wg')) {
            $psk = trim((string) shell_exec('wg genpsk 2>/dev/null'));
            return $psk !== '' ? $psk : null;
        }

        return base64_encode(random_bytes(32));
    }

    private function binaryExists(string $binary): bool
    {
        $result = trim((string) shell_exec("command -v {$binary} 2>/dev/null"));
        return $result !== '';
    }
}
