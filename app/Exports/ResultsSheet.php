<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ResultsSheet implements FromArray, WithTitle, WithHeadings
{
    public function __construct(protected array $results) {}

    public function array(): array
    {
        return $this->results;
    }

    public function headings(): array
    {
        // Уніфіковані заголовки
        return [
            'EIC-код',
            'Обсяг, кВт·год',
            'Назва споживача',
            'Група (А/Б)',
        ];
    }

    public function title(): string
    {
        return 'Зведений результат';
    }
}