<?php

namespace App\Livewire;

use App\Models\EnergyCompany;
use App\Models\ConsumptionProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        $profilesQuery = ConsumptionProfile::query()
            ->where('energy_company_id', $this->selectedCompanyId)
            ->whereBetween('profile_date', [$startDate, $endDate])
            ->whereIn(DB::raw('(profile_date, version)'), function ($query) {
                $query->select('profile_date', DB::raw('MAX(version)'))
                    ->from('consumption_profiles')
                    ->where('energy_company_id', $this->selectedCompanyId)
                    ->groupBy('profile_date');
            })
            ->orderBy('profile_date', 'asc');

        $profiles = $profilesQuery->get();
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