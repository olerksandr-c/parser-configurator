<?php

namespace App\Services;

use App\Models\ParsingTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ParsingService
{
    // Словник для заміни кириличних символів-двійників на латиницю
    private const EIC_CYRILLIC_MAP = [
        'А', 'В', 'Е', 'К', 'М', 'Н', 'О', 'Р', 'С', 'Т', 'У', 'Х', 'І',
        'а', 'в', 'е', 'к', 'м', 'н', 'о', 'р', 'с', 'т', 'у', 'х', 'і',
    ];
    private const EIC_LATIN_MAP = [
        'A', 'B', 'E', 'K', 'M', 'H', 'O', 'P', 'S', 'T', 'Y', 'X', 'I',
        'A', 'B', 'E', 'K', 'M', 'H', 'O', 'P', 'S', 'T', 'Y', 'X', 'I',
    ];

    /**
     * Знаходить відповідний шаблон для файлу за його іменем.
     */
    public function findTemplateForFile(string $filename): ?ParsingTemplate
    {
        // Ми можемо кешувати шаблони для швидкодії
        $templates = ParsingTemplate::all();

        foreach ($templates as $template) {
            if (Str::contains($filename, $template->file_pattern, true)) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Очищує та валідує EIC-код.
     */
    public function cleanEic(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }
        // Замінюємо кириличні аналоги на латиницю
        $value = str_replace(self::EIC_CYRILLIC_MAP, self::EIC_LATIN_MAP, $value);
        // Видаляємо всі символи, крім дозволених
        $value = preg_replace('/[^A-Z0-9]/', '', strtoupper($value));

        return strlen($value) === 16 ? $value : null;
    }

    /**
     * Очищує числове значення.
     */
    public function cleanNumber(?string $value): ?float
    {
        if (blank($value)) {
            return null;
        }
        $value = str_replace([' ', ','], ['', '.'], $value);

        return is_numeric($value) ? (float)$value : null;
    }

    /**
     * Основний метод, що парсить файл за допомогою шаблону.
     */
    public function parseFile(UploadedFile $file, ParsingTemplate $template): array
    {
        $config = $template->config;
        $collection = Excel::toCollection(collect(), $file)->first();

        $dataRows = $collection->slice($config['headerRow']);

        $results = [];
        foreach ($dataRows as $row) {
            // Застосовуємо фільтри з шаблону
            $shouldSkip = false;
            foreach ($config['filters'] as $filter) {
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
                if (!$match) {
                    $shouldSkip = true;
                    break;
                }
            }

            if ($shouldSkip) continue;

            // Витягуємо та очищуємо дані згідно з мапінгом
            $mappedRow = [];
            foreach ($config['mappings'] as $index => $type) {
                if (!$type) continue;

                $cellValue = Arr::get($row, $index);
                $cleanedValue = match($type) {
                    'eic' => $this->cleanEic($cellValue),
                    'volume' => $this->cleanNumber($cellValue),
                    default => trim((string)$cellValue),
                };
                $mappedRow[$type] = $cleanedValue;
            }

            // Додаємо рядок до результатів, тільки якщо є EIC та обсяг
            if (!empty($mappedRow['eic']) && isset($mappedRow['volume'])) {
                $results[] = $mappedRow;
            }
        }

        return $results;
    }
}