<?php

namespace App\Actions;

use App\Models\ConsumptionProfile;
use App\Models\EnergyCompany;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessCsvImportAction
{
    /**
     * Виконує обробку завантаженого CSV-файлу.
     */
    public function execute(string $filePath, int $version): void
    {
        // 1. Отримуємо всі компанії одним запитом для ефективності.
        $companies = EnergyCompany::pluck('id', 'u_code');

        // 2. Читаємо весь файл у вигляді рядків.
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            throw new \Exception('Файл порожній або не вдалося його прочитати.');
        }

        // 3. Обробляємо заголовок (перший рядок).
        $headerLine = array_shift($lines);
        
        // Надійно видаляємо BOM з першого рядка.
        if (str_starts_with($headerLine, "\xef\xbb\xbf")) {
            $headerLine = substr($headerLine, 3);
        }
        $header = str_getcsv($headerLine, ';');

        // Створюємо карту колонок: [індекс_колонки => ['company_id' => id, 'multiplier' => 1000|1]]
        $columnsMap = [];
        foreach ($header as $index => $colName) {
            if ($index === 0 || empty($colName)) {
                continue;
            }
            $uCode = strtok($colName, '_');
            if ($companies->has($uCode)) {
                $columnsMap[$index] = [
                    'company_id' => $companies[$uCode],
                    'multiplier' => str_contains($colName, 'KWH') ? 1000.0 : 1.0,
                ];
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
                $dateTime = Carbon::createFromFormat('d.m.Y H:i', substr($row[0], 0, 16));
                $date = $dateTime->format('Y-m-d');
                $hour = $dateTime->hour + 1;
            } catch (\Exception $e) {
                continue; // Пропускаємо рядок з некоректною датою.
            }

            foreach ($columnsMap as $columnIndex => $map) {
                $companyId = $map['company_id'];
                $value = (float) str_replace(',', '', $row[$columnIndex] ?? '0');
                $valueInMwh = $value / $map['multiplier'];
                
                $totals[$companyId] = ($totals[$companyId] ?? 0.0) + $valueInMwh;
                $consumptionData[$companyId][$date][$hour] = ($consumptionData[$companyId][$date][$hour] ?? 0.0) + $valueInMwh;
            }
        }

        // 5. Розраховуємо коефіцієнти і готуємо масив для збереження.
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
                    $coefficient = ($totals[$companyId] > 0) ? ($hourlyValue / $totals[$companyId]) : 0;
                    $profileData['h' . $h] = $coefficient;
                }
                $profilesToUpsert[] = $profileData;
            }
        }

        if (empty($profilesToUpsert)) {
            throw new \Exception('Не знайдено даних для імпорту.');
        }

        // 6. Зберігаємо все в базу даних.
        DB::transaction(function () use ($profilesToUpsert) {
            foreach (array_chunk($profilesToUpsert, 200) as $chunk) {
                ConsumptionProfile::upsert(
                    $chunk,
                    uniqueBy: ['energy_company_id', 'profile_date', 'version'],
                    update: array_merge(array_map(fn($i) => 'h'.$i, range(1, 25)), ['updated_at'])
                );
            }
        });
    }
}