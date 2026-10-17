<?php

return [
    // Itakda sa false sa .env (REGISTRATION_ENABLED=false) kapag live na ang system
    // para walang makagawa ng sariling account.
    'registration_enabled' => env('REGISTRATION_ENABLED', true),
];
