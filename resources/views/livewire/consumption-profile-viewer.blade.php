<div>
    <div class="max-w-7xl mx-auto p-6 bg-white rounded-lg shadow-md space-y-8">
        {{-- Основний блок з фільтрами та таблицею коефіцієнтів --}}
        <div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">Перегляд профілів споживання</h2>
                {{-- ДОДАНО: Кнопка очищення з підтвердженням --}}
                <button type="button"
                    wire:click="clearAllProfiles"
                    wire:confirm="Ви впевнені, що хочете видалити всі профілі? Ця дія незворотна."
                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                    Очистити все
                </button>
            </div>

            {{-- ДОДАНО: Блок для повідомлень --}}
            @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
            @endif

            {{-- Секція з фільтрами --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-4 border rounded-md items-end">
                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700">ОСР</label>
                    <select id="company" wire:model.defer="selectedCompanyId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="month" class="block text-sm font-medium text-gray-700">Місяць</label>
                    <select id="month" wire:model.defer="selectedMonth" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}">{{ \Carbon\Carbon::create(null, $i)->monthName }}</option>
                            @endfor
                    </select>
                </div>
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700">Рік</label>
                    <select id="year" wire:model.defer="selectedYear" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @for ($i = now()->year; $i >= now()->year - 5; $i--)
                        <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <button type="button" wire:click="applyFilters"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150 w-full justify-center"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="applyFilters">Застосувати</span>
                        <span wire:loading wire:target="applyFilters">Застосування...</span>
                    </button>
                </div>
            </div>

            {{-- Таблиця з даними --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Версія</th>
                            @for ($h = 1; $h <= 25; $h++)
                                <th class="px-2 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">h{{ $h }}</th>
                                @endfor
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($profiles as $profile)
                        <tr>
                            <td class="px-2 py-4 whitespace-nowrap text-sm">{{ $profile->profile_date->format('d.m.Y') }}</td>
                            <td class="px-2 py-4 whitespace-nowrap text-sm text-center">
                                @if(abs($profile->coefficient_sum - 1) < 0.000001)
                                    <span title="Сума: {{ number_format($profile->coefficient_sum, 8) }}" class="inline-flex items-center justify-center w-6 h-6 bg-green-100 text-green-800 rounded-full font-bold">
                                    ✓
                                    </span>
                                    @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        {{ number_format($profile->coefficient_sum, 4) }}
                                    </span>
                                    @endif
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap text-sm text-center">{{ $profile->version }}</td>
                            @for ($h = 1; $h <= 25; $h++)
                                <td class="px-2 py-4 whitespace-nowrap text-sm text-right font-mono">{{ number_format($profile->{'h'.$h}, 8, '.', '') }}</td>
                                @endfor
                        </tr>
                        @empty
                        <tr>
                            <td colspan="28" class="text-center py-4 text-gray-500">Немає даних для вибраного періоду.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr>
                            <td class="px-2 py-3 text-left text-sm uppercase">Підсумок</td>
                            <td class="px-2 py-3 text-center text-sm">
                                {{-- ВИПРАВЛЕНО: Порівнюємо підсумок з 1, а не з кількістю профілів --}}
                                <span class="px-2 inline-flex text-xs leading-5 rounded-full {{ abs($grandTotal - 1) < 0.0001 && $profiles->count() > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ number_format($grandTotal, 4, '.', '') }}
                                </span>
                            </td>
                            <td class="px-2 py-3"></td>
                            <td colspan="25" class="px-2 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <hr>

        {{-- Зведена інформація --}}
        <div>
            <h2 class="text-2xl font-bold mb-4">Зведена інформація по профілях</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ОСР</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Період</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Наявні версії</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($profilesSummary as $summary)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $summary->company_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ \Carbon\Carbon::create($summary->year, $summary->month)->format('F Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">{{ $summary->versions }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-gray-500">Немає завантажених профілів.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>