<?php

return [
    'ssh_host' => env('WG_SSH_HOST'),
    'ssh_user' => env('WG_SSH_USER', 'phenixwg'),
    'ssh_port' => env('WG_SSH_PORT', 22),
    'ssh_private_key_path' => env('WG_SSH_KEY_PATH'), // ex: /var/www/app/storage/app/keys/wg_id_rsa
];