<?php

return [
    // Number of days after due_date before an overdue customer gets isolated on MikroTik.
    'isolation_grace_days' => (int) env('ISOLATION_GRACE_DAYS', 3),

    // Number of days before due_date to auto-generate a customer's next invoice.
    'invoice_generate_days_before' => (int) env('INVOICE_GENERATE_DAYS_BEFORE', 7),

    // Reminder stages per channel: days relative to due_date (positive = before, negative = after) => stage type.
    'reminder_offsets' => [
        'email' => [
            3 => 'reminder_h-3',
            0 => 'reminder_h0',
            -3 => 'reminder_h+3',
            -7 => 'reminder_h+7',
        ],
        'whatsapp' => [
            0 => 'reminder_h0',
            -3 => 'reminder_h+3',
            -7 => 'reminder_h+7',
        ],
    ],
];
