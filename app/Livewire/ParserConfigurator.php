<?php

namespace App\Livewire;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Livewire\Attributes\Computed;
use App\Models\ParsingTemplate;

class ParserConfigurator extends Component
{
    use WithFileUploads;

    public $file;
    public array $mappingOptions = [
        'eic'           => 'EIC-код',
        'volume'        => 'Обсяг, кВт·год',
        'consumer_name' => 'Назва споживача',
        'group'         => 'Група (А/Б)',
    ];
    public array $mappings = [];
    public int $headerRowNumber = 1;
    public ?string $fileError = null;
    public Collection $rawRows;
    public array $filters = [];
    public array $conditions = [
        'contains' => 'Містить',
        'not_contains' => 'Не містить',
        'equals' => 'Дорівнює',
        'not_equals' => 'Не дорівнює',
        'is_empty' => 'Пустий',
        'is_not_empty' => 'Не пустий',
    ];
    
    public string $templateName = '';
    public string $filePattern = '';
    public ?string $successMessage = null;

    public function mount()
    {
        $this->rawRows = collect();
    }

    // Метод updated() було повністю видалено, щоб прибрати автоматизацію.

    public function updatedFile()
    {
        $this->validate(['file' => 'required|mimes:xlsx,xls|max:10240']);
        $this->reset(['mappings', 'fileError', 'filters', 'headerRowNumber']);
        $this->processFile();
    }

    private function processFile(): void
    {
        $this->reset('fileError');
        try {
            $this->rawRows = Excel::toCollection(collect(), $this->file)->first()?->take(50) ?? collect();
            if ($this->rawRows->isEmpty()) {
                $this->fileError = 'Файл порожній або не вдалося його прочитати.';
                return;
            }
            $this->mappings = array_fill(0, $this->rawRows->first()->count(), null);
        } catch (Exception $e) {
            $this->fileError = 'Виникла помилка при обробці файлу.';
            Log::error('File processing error: ' . $e->getMessage());
        }
    }

    #[Computed]
    public function headers(): array
    {
        return $this->rawRows->get($this->headerRowNumber - 1, collect())->toArray();
    }

    #[Computed]
    public function resultHeaders(): array
    {
        $result = [];
        foreach ($this->mappings as $index => $type) {
            if ($type) {
                $result[$index] = $this->mappingOptions[$type] ?? "Стовпець {$index}";
            }
        }
        return $result;
    }

    #[Computed]
    public function resultRows(): Collection
    {
        $data = $this->rawRows->slice($this->headerRowNumber);
        $filteredData = $data->filter(function ($row) {
            foreach ($this->filters as $filter) {
                if (!isset($filter['columnIndex']) || $filter['columnIndex'] === '' || empty($filter['condition'])) continue;
                $cellValue = Arr::get($row, $filter['columnIndex']);
                $filterValue = $filter['value'] ?? '';
                $match = match ($filter['condition']) {
                    'contains' => Str::contains((string)$cellValue, $filterValue, true),
                    'not_contains' => !Str::contains((string)$cellValue, $filterValue, true),
                    'equals' => (string)$cellValue === $filterValue,
                    'not_equals' => (string)$cellValue !== $filterValue,
                    'is_empty' => blank($cellValue),
                    'is_not_empty' => filled($cellValue),
                    default => true,
                };
                if (!$match) return false;
            }
            return true;
        });
        $mappedIndexes = array_keys(array_filter($this->mappings));
        return $filteredData->map(fn($row) => $row->only($mappedIndexes));
    }

    public function addFilter(): void
    {
        // Спрощено: прибираємо тег is_default
        $this->filters[] = ['columnIndex' => '', 'condition' => 'contains', 'value' => ''];
    }

    public function removeFilter(int $index): void
    {
        unset($this->filters[$index]);
        $this->filters = array_values($this->filters);
    }

    public function isCellHighlighted(int $columnIndex, $cellValue): bool
    {
        foreach ($this->filters as $filter) {
            if (($filter['columnIndex'] ?? null) != $columnIndex) continue;
            $filterValue = $filter['value'] ?? '';
            $condition = $filter['condition'] ?? '';
            if (in_array($condition, ['contains', 'equals']) && !empty($filterValue) && Str::contains((string)$cellValue, $filterValue, true)) return true;
            if ($condition === 'is_empty' && blank($cellValue)) return true;
            if ($condition === 'is_not_empty' && filled($cellValue)) return true;
        }
        return false;
    }

    public function save()
    {
        $this->validate([
            'templateName' => 'required|string|min:3|unique:parsing_templates,name',
            'filePattern' => 'required|string|min:3',
        ]);

        // Перевіряємо, чи налаштовано хоча б один стовпець
        if (empty(array_filter($this->mappings))) {
            $this->addError('mappings', 'Необхідно налаштувати хоча б один стовпець.');
            return;
        }

        $configData = [
            'mappings' => $this->mappings,
            'headerRow' => $this->headerRowNumber,
            'filters' => $this->filters,
        ];

        ParsingTemplate::create([
            'name' => $this->templateName,
            'file_pattern' => $this->filePattern,
            'config' => $configData,
        ]);

        $this->successMessage = "Шаблон '{$this->templateName}' успішно збережено!";

        // Опціонально: можна скинути стан форми після збереження
        // $this->reset(['templateName', 'filePattern', 'file', 'rawRows', 'filters', 'mappings']);
    }

    public function render()
    {
        return view('livewire.parser-configurator');
    }
}
