<?php

namespace App\Livewire;

use App\Exports\ResultsExport;
use App\Services\ParsingService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class FileProcessor extends Component
{
    use WithFileUploads;

    public $uploadedFiles = [];
    public array $statusLog = [];
    public bool $processingFinished = false;

    public function process(ParsingService $parsingService)
    {
        $this->validate([
            'uploadedFiles.*' => 'required|mimes:xlsx,xls|max:10240' // 10MB Max per file
        ]);

        $this->processingFinished = false;
        $this->statusLog = [];
        $aggregatedResults = [];

        foreach ($this->uploadedFiles as $file) {
            $filename = $file->getClientOriginalName();
            $template = $parsingService->findTemplateForFile($filename);

            if (!$template) {
                $this->statusLog[] = [$filename, '(не знайдено)', 'Пропущено', 0];
                continue;
            }

            $parsedData = $parsingService->parseFile($file, $template);

            // Додаємо дані до загального списку
            foreach ($parsedData as $row) {
                 $aggregatedResults[] = [
                    'eic' => $row['eic'] ?? null,
                    'volume' => $row['volume'] ?? null,
                    'consumer_name' => $row['consumer_name'] ?? null,
                    'group' => $row['group'] ?? null,
                ];
            }

            $this->statusLog[] = [$filename, $template->name, 'Оброблено', count($parsedData)];
        }

        $this->processingFinished = true;

        // Якщо є результати, генеруємо і віддаємо файл
        if (!empty($aggregatedResults)) {
            return Excel::download(new ResultsExport($aggregatedResults, $this->statusLog), 'parsing_result.xlsx');
        }
    }

    public function render()
    {
        return view('livewire.file-processor');
    }
}