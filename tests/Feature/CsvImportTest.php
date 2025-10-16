<?php

namespace Tests\Feature;

use App\Actions\ProcessCsvImportAction;
use App\Models\EnergyCompany;
use App\Models\ConsumptionProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем тестовые компании
        EnergyCompany::create(['id' => 1, 'u_code' => 'MGA-01000', 'name' => 'Test Company 1']);
        EnergyCompany::create(['id' => 2, 'u_code' => 'MGA-01400', 'name' => 'Test Company 2']);
    }

    /** @test */
    public function it_can_import_csv_file()
    {
        // Подготавливаем тестовые данные CSV
        $csvContent = "Datapoint Unit;MGA-01000_UTEC-B_UA-IPS KWH;MGA-01400_UTEC-B_UA-IPS KWH\n" .
                     "01.08.2025 00:00;1000;2000\n" .
                     "01.08.2025 01:00;1500;2500\n";

        // Сохраняем CSV во временный файл
        Storage::fake('private');
        $filePath = Storage::disk('private')->path('test.csv');
        file_put_contents($filePath, $csvContent);

        // Запускаем импорт
        $action = new ProcessCsvImportAction();
        $action->execute($filePath, 1);

        // Проверяем результаты
        $this->assertDatabaseHas('consumption_profiles', [
            'energy_company_id' => 1,
            'profile_date' => '2025-08-01',
            'version' => 1
        ]);

        // Получаем записи из базы данных
        $profile1 = ConsumptionProfile::where('energy_company_id', 1)
            ->where('profile_date', '2025-08-01')
            ->first();

        $profile2 = ConsumptionProfile::where('energy_company_id', 2)
            ->where('profile_date', '2025-08-01')
            ->first();

        // Проверяем значения для первой компании
        $totalCompany1 = 1000 + 1500;
        $this->assertEquals(round(1000/$totalCompany1, 8), $profile1->h1);
        $this->assertEquals(round(1500/$totalCompany1, 8), $profile1->h2);

        // Проверяем значения для второй компании
        $totalCompany2 = 2000 + 2500;
        $this->assertEquals(round(2000/$totalCompany2, 8), $profile2->h1);
        $this->assertEquals(round(2500/$totalCompany2, 8), $profile2->h2);
    }
}