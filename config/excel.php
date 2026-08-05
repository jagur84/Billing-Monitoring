<?php

return [
    // Overrides only this key from the package default (config/excel.php isn't fully
    // published — Laravel merges the rest in from maatwebsite/excel's own config).
    // See App\Exports\SafeValueBinder for why: guards every export against CSV/Excel
    // formula injection from customer- or import-supplied fields.
    'value_binder' => [
        'default' => \App\Exports\SafeValueBinder::class,
    ],
];
