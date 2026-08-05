<?php

namespace Tests\Unit\Exports;

use App\Exports\SafeValueBinder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class SafeValueBinderTest extends TestCase
{
    /** @dataProvider riskyValueProvider */
    public function test_it_neutralizes_formula_triggering_leading_characters(string $input): void
    {
        $cell = (new Spreadsheet)->getActiveSheet()->getCell('A1');

        (new SafeValueBinder)->bindValue($cell, $input);

        $this->assertSame("'".$input, $cell->getValue());
    }

    public static function riskyValueProvider(): array
    {
        return [
            'equals (formula)' => ['=cmd|\'/c calc\'!A1'],
            'plus' => ['+1+1'],
            'minus' => ['-1+1'],
            'at (DDE)' => ['@SUM(1,1)'],
        ];
    }

    public function test_it_leaves_ordinary_values_untouched(): void
    {
        $cell = (new Spreadsheet)->getActiveSheet()->getCell('A1');

        (new SafeValueBinder)->bindValue($cell, 'Budi Santoso');

        $this->assertSame('Budi Santoso', $cell->getValue());
    }
}
