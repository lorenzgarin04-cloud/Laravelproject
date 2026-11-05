<?php

return [
    // Naka-OFF bilang default: si Admin lang ang gumagawa ng accounts (Users page).
    // Itakda sa true sa .env (REGISTRATION_ENABLED=true) kung gusto ng public sign-up.
    // Palaging bukas ang Register kapag wala pang kahit isang user (para sa unang Admin).
    'registration_enabled' => env('REGISTRATION_ENABLED', false),

    // Oras ng tindahan. Puwedeng palitan sa .env: APP_TIMEZONE=Asia/Manila
    'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),
];
