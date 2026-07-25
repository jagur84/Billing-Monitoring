<?php

return [
    // Self-hosted WhatsApp engine (Baileys), see docker/whatsapp/.
    'engine_url' => env('WHATSAPP_ENGINE_URL', 'http://whatsapp:3000'),
];
