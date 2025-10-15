<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StatusSheet implements FromArray, WithTitle, WithHeadings
{
    public function __construct(protected array $statusLog) {}

    public function array(): array
    {
        return $this->statusLog;
    }

    public function headings(): array
    {
        return ['Файл', 'Шаблон', 'Статус', 'Знайдено рядків'];
    }

    public function title(): string
    {
        return 'Статус';
    }
}