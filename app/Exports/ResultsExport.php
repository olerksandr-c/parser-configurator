<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ResultsExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected array $results,
        protected array $statusLog
    ) {}

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new ResultsSheet($this->results),
            new StatusSheet($this->statusLog),
        ];
    }
}