<?php

namespace App\Services;

use App\Models\Router;
use Illuminate\Support\Facades\Log;

/**
 * Service pour interagir avec FreeRADIUS de manière proactive (CoA/Disconnect).
 */
class RadiusManagementService
{
    /**
     * Envoie une requête de déconnexion (Packet of Disconnect) au NAS via FreeRADIUS.
     * * @param string $username Le code du ticket ou nom d'utilisateur.
     * @param string $nasIp L'IP du Mikrotik (NAS-IP-Address).
     * @param string $secret Le secret partagé RADIUS (nécessaire pour signer le paquet).
     * @return bool
     */
    public function disconnectUser(string $username, string $nasIp, string $secret): bool
    {
        try {
            // Commande shell pour envoyer un paquet de déconnexion.
            // Note: radclient doit être installé sur le serveur web ou via une gateway.
            // Format: echo "User-Name=code" | radclient -x <NAS_IP>:1700 disconnect <SECRET>
            
            $command = sprintf(
                'echo "User-Name=%s" | radclient -t 2 -r 2 -x %s:1700 disconnect %s 2>&1',
                escapeshellarg($username),
                escapeshellarg($nasIp),
                escapeshellarg($secret)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                Log::error("Erreur Disconnect RADIUS ($username): " . implode(' ', $output));
                return false;
            }

            Log::info("Utilisateur $username déconnecté avec succès du NAS $nasIp");
            return true;

        } catch (\Exception $e) {
            Log::error("Exception lors de la déconnexion RADIUS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Change les limites d'un utilisateur en session (Ex: bridage de vitesse immédiat).
     */
    public function changeUserRateLimit(string $username, string $nasIp, string $secret, string $newRate): bool
    {
        // Utilise CoA (Change of Authorization) sur le port 1700
        $command = sprintf(
            'echo "User-Name=%s,Mikrotik-Rate-Limit=%s" | radclient -x %s:1700 coa %s 2>&1',
            escapeshellarg($username),
            escapeshellarg($newRate),
            escapeshellarg($nasIp),
            escapeshellarg($secret)
        );

        exec($command, $output, $returnVar);
        return $returnVar === 0;
    }
}