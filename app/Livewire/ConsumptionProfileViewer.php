<?php

namespace App\Livewire;

use App\Models\EnergyCompany;
use App\Models\ConsumptionProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ConsumptionProfileViewer extends Component
{
    public $selectedCompanyId;
    public $selectedMonth;
    public  $selectedYear;

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->selectedMonth = now()->month;
        $this->selectedCompanyId = EnergyCompany::orderBy('name')->first()->id ?? null;
    }

    public function applyFilters()
    {
        // Порожній метод для кнопки
    }

    /**
     * ДОДАНО: Метод для повного очищення таблиці профілів.
     */
    public function clearAllProfiles()
    {
        ConsumptionProfile::truncate(); // Видаляє всі записи з таблиці
        session()->flash('success', 'Все профили были успешно удалены.');
        // Оновлюємо дані на сторінці, щоб таблиця одразу стала порожньою
        $this->render(); 
    }

    public function render()
    {
    $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();
    $startDateStr = $startDate->format('Y-m-d');
    $endDateStr = $endDate->format('Y-m-d');

        // Сначала получаем максимальные версии для каждой даты
        $maxVersions = ConsumptionProfile::query()
            ->select('profile_date')
            ->selectRaw('MAX(version) as max_version')
            ->where('energy_company_id', $this->selectedCompanyId)
            ->whereBetween('profile_date', [$startDateStr, $endDateStr])
            ->groupBy('profile_date');

        // Явно указываем поля для выборки, чтобы избежать конфликтов
        $profilesQuery = ConsumptionProfile::query()
            ->select([
                'consumption_profiles.id',
                'consumption_profiles.energy_company_id',
                'consumption_profiles.profile_date',
                'consumption_profiles.version',
                'consumption_profiles.h1','consumption_profiles.h2','consumption_profiles.h3','consumption_profiles.h4','consumption_profiles.h5',
                'consumption_profiles.h6','consumption_profiles.h7','consumption_profiles.h8','consumption_profiles.h9','consumption_profiles.h10',
                'consumption_profiles.h11','consumption_profiles.h12','consumption_profiles.h13','consumption_profiles.h14','consumption_profiles.h15',
                'consumption_profiles.h16','consumption_profiles.h17','consumption_profiles.h18','consumption_profiles.h19','consumption_profiles.h20',
                'consumption_profiles.h21','consumption_profiles.h22','consumption_profiles.h23','consumption_profiles.h24','consumption_profiles.h25',
                'consumption_profiles.created_at',
                'consumption_profiles.updated_at'
            ])
            ->where('consumption_profiles.energy_company_id', $this->selectedCompanyId)
            ->whereBetween('consumption_profiles.profile_date', [$startDateStr, $endDateStr])
            ->joinSub($maxVersions, 'max_versions', function($join) {
                $join->on('consumption_profiles.profile_date', '=', 'max_versions.profile_date')
                     ->whereColumn('consumption_profiles.version', '=', 'max_versions.max_version');
            })
            ->orderBy('consumption_profiles.profile_date', 'asc');

        // Логируем SQL запрос
        Log::info('Profile query', [
            'sql' => $profilesQuery->toSql(),
            'bindings' => $profilesQuery->getBindings()
        ]);

        $profiles = $profilesQuery->get();
        
        // Логируем результаты
        Log::info('Profiles found', [
            'count' => $profiles->count(),
            'dates' => $profiles->pluck('profile_date')->toArray()
        ]);

        $grandTotal = 0.0;

        $profiles->transform(function ($profile) {
            $sum = 0.0;
            for ($h = 1; $h <= 25; $h++) {
                $sum += $profile->{'h'.$h};
            }
            $profile->coefficient_sum = $sum;
            return $profile;
        });
        
        $grandTotal = $profiles->sum('coefficient_sum');

        $profilesSummary = DB::table('consumption_profiles as cp')
            ->join('energy_companies as ec', 'cp.energy_company_id', '=', 'ec.id')
            ->select(
                'ec.name as company_name',
                DB::raw("strftime('%Y', cp.profile_date) as year"),
                DB::raw("strftime('%m', cp.profile_date) as month"),
                DB::raw('GROUP_CONCAT(DISTINCT cp.version ORDER BY cp.version ASC) as versions')
            )
            ->groupBy('ec.name', 'year', 'month')
            ->orderBy('ec.name')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $companies = EnergyCompany::orderBy('name')->get();

        return view('livewire.consumption-profile-viewer', [
            'companies' => $companies,
            'profiles' => $profiles,
            'profilesSummary' => $profilesSummary,
            'grandTotal' => $grandTotal
        ]);
    }
}