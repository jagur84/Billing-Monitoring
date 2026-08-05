<?php

return [
    // Self-hosted WhatsApp engine (Baileys), see docker/whatsapp/.
    'engine_url' => env('WHATSAPP_ENGINE_URL', 'http://whatsapp:3000'),

    // Gap between consecutive reminder sends in a single batch (notifications:send-reminders
    // can queue dozens at once) — Baileys uses the unofficial WhatsApp Web protocol, and bursts
    // of messages with no delay are what typically get a number flagged/banned.
    'reminder_delay_seconds' => (int) env('WHATSAPP_REMINDER_DELAY_SECONDS', 8),
];
