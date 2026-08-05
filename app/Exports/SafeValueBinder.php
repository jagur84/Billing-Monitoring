<?php

namespace App\Exports;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

/**
 * Guards every export in the app against CSV/Excel formula injection: a string cell whose
 * value starts with =, +, -, @, or a tab/CR (the characters Excel/Sheets treat as the start
 * of a formula) gets a leading apostrophe so it's stored as literal text instead of executing
 * when a customer- or import-supplied field (e.g. a crafted customer name) is opened by staff.
 * Wired in globally via config/excel.php's value_binder.default — no per-export changes needed.
 */
class SafeValueBinder extends DefaultValueBinder
{
    private const RISKY_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && $value !== '' && in_array($value[0], self::RISKY_PREFIXES, true)) {
            $value = "'".$value;
        }

        return parent::bindValue($cell, $value);
    }
}
