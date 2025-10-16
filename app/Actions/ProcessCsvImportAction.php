<?php

namespace App\Actions;

use App\Models\ConsumptionProfile;
use App\Models\EnergyCompany;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCsvImportAction
{
    /**
     * Виконує обробку завантаженого CSV-файлу.
     */
    public function execute(string $filePath, int $version): void
    {
        Log::info('Starting CSV import', ['file' => $filePath, 'version' => $version]);
        
        // 1. Отримуємо всі компанії одним запитом для ефективності.
        $companies = EnergyCompany::pluck('id', 'u_code');
        Log::info('Found companies', ['count' => $companies->count(), 'companies' => $companies->toArray()]);

        // 2. Читаємо весь файл у вигляді рядків.
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            throw new \Exception('Файл порожній або не вдалося його прочитати.');
        }
        
        // Логируем первые несколько строк файла для отладки
        $firstLines = array_slice($lines, 0, 5);
        Log::info('First lines from file', ['lines' => $firstLines]);

        // 3. Обробляємо заголовок (перший рядок).
        $headerLine = array_shift($lines);
        Log::info('Processing header line', ['header' => $headerLine]);
        
        // Надійно видаляємо BOM з першого рядка.
        if (str_starts_with($headerLine, "\xef\xbb\xbf")) {
            $headerLine = substr($headerLine, 3);
            Log::info('BOM removed from header');
        }
        $header = str_getcsv($headerLine, ';');
        Log::info('Parsed header', ['columns' => $header]);

        // Створюємо карту колонок: [індекс_колонки => ['company_id' => id, 'multiplier' => 1000|1]]
        $columnsMap = [];
        foreach ($header as $index => $colName) {
            if ($index === 0 || empty($colName)) {
                continue;
            }
            $uCode = strtok($colName, '_');
            Log::info('Processing column', ['index' => $index, 'colName' => $colName, 'uCode' => $uCode]);
            if ($companies->has($uCode)) {
                $columnsMap[$index] = [
                    'company_id' => $companies[$uCode],
                    'multiplier' => str_contains($colName, 'KWH') ? 1000.0 : 1.0,
                ];
                Log::info('Mapped column', ['index' => $index, 'uCode' => $uCode, 'company_id' => $companies[$uCode], 'multiplier' => str_contains($colName, 'KWH') ? 1000.0 : 1.0]);
            } else {
                Log::warning('Column not mapped - company code not found', ['index' => $index, 'colName' => $colName, 'uCode' => $uCode]);
            }
        }

        if (empty($columnsMap)) {
            throw new \Exception('У заголовках файлу не знайдено жодної відомої компанії.');
        }

        // 4. Обробляємо всі рядки з даними, накопичуючи значення.
        $consumptionData = []; // Структура: [company_id][date][hour] = value
        $totals = [];          // Структура: [company_id] = total_value

        foreach ($lines as $line) {
            $row = str_getcsv($line, ';');

            if (empty($row[0]) || count($row) < 2) {
                continue;
            }

            try {
                $rawDate = substr($row[0], 0, 16);
                Log::info('Processing date', [
                    'raw' => $rawDate,
                    'full_row' => $row[0],
                    'row_length' => strlen($row[0]),
                    'substr_result' => substr($row[0], 0, 16)
                ]);
                
                $dateTime = Carbon::createFromFormat('d.m.Y H:i', $rawDate);
                if ($dateTime === false) {
                    Log::error('Failed to parse date with Carbon', [
                        'raw' => $rawDate,
                        'parse_errors' => Carbon::getLastErrors()
                    ]);
                    continue;
                }
                
                $date = $dateTime->format('Y-m-d');
                $hour = $dateTime->hour + 1;
                Log::info('Date parsed successfully', [
                    'raw' => $rawDate,
                    'parsed_date' => $date,
                    'hour' => $hour,
                    'month' => $dateTime->month,
                    'year' => $dateTime->year
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to parse date', [
                    'raw' => $row[0],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue; // Пропускаємо рядок з некоректною датою.
            }

            foreach ($columnsMap as $columnIndex => $map) {
                $companyId = $map['company_id'];
                $rawValue = $row[$columnIndex] ?? '0';
                Log::info('Processing value', [
                    'date' => $date,
                    'hour' => $hour,
                    'companyId' => $companyId,
                    'columnIndex' => $columnIndex,
                    'rawValue' => $rawValue
                ]);
                
                $value = (float) str_replace(',', '', $rawValue);
                $valueInMwh = $value / $map['multiplier'];
                
                $totals[$companyId] = ($totals[$companyId] ?? 0.0) + $valueInMwh;
                $consumptionData[$companyId][$date][$hour] = ($consumptionData[$companyId][$date][$hour] ?? 0.0) + $valueInMwh;
            }
        }

        // 5. Розраховуємо коефіцієнти і готуємо масив для збереження.
        Log::info('Totals by company', ['totals' => $totals]);
        
        $profilesToUpsert = [];
        foreach ($consumptionData as $companyId => $dates) {
            foreach ($dates as $date => $hours) {
                $profileData = [
                    'energy_company_id' => $companyId,
                    'profile_date' => $date,
                    'version' => $version,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                for ($h = 1; $h <= 25; $h++) {
                    $hourlyValue = $hours[$h] ?? 0;
                    $coefficient = ($totals[$companyId] > 0) ? round($hourlyValue / $totals[$companyId], 8) : 0;
                    $profileData['h' . $h] = $coefficient;
                }
                $profilesToUpsert[] = $profileData;
            }
        }

        if (empty($profilesToUpsert)) {
            Log::error('No data to import');
            throw new \Exception('Не знайдено даних для імпорту.');
        }

        Log::info('Preparing to save profiles', ['count' => count($profilesToUpsert)]);
        // 6. Зберігаємо все в базу даних.
        Log::info('Preparing to save data', ['profiles' => $profilesToUpsert]);
        
        DB::transaction(function () use ($profilesToUpsert) {
            foreach (array_chunk($profilesToUpsert, 200) as $chunk) {
                try {
                    ConsumptionProfile::upsert(
                        $chunk,
                        uniqueBy: ['energy_company_id', 'profile_date', 'version'],
                        update: array_merge(array_map(fn($i) => 'h'.$i, range(1, 25)), ['updated_at'])
                    );
                    Log::info('Chunk saved successfully', ['count' => count($chunk)]);
                } catch (\Exception $e) {
                    Log::error('Failed to save chunk', [
                        'error' => $e->getMessage(),
                        'chunk' => $chunk
                    ]);
                    throw $e;
                }
            }
        });
    }
}