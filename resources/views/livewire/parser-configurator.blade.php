<div>
    <div class="p-8 bg-white shadow-md rounded-lg">
        <h2 class="text-2xl font-bold mb-4">Конфігуратор шаблонів парсингу</h2>

        {{-- БЛОК ЗАВАНТАЖЕННЯ ФАЙЛУ --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">1. Завантажте зразок Excel-файлу</label>
            <input type="file" wire:model="file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100" />
        </div>

        @if ($rawRows->isNotEmpty())
        <hr class="my-6">

        {{-- ОБ'ЄДНАНА ТАБЛИЦЯ ДЛЯ НАЛАШТУВАННЯ --}}
        <p class="block text-sm font-medium text-gray-700 mb-2">2. Виберіть рядок із заголовками та налаштуйте стовпці</p>
        <div class="overflow-x-auto border border-gray-300 rounded-lg">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-2 border-r border-b"></th>
                        @foreach ($this->headers as $index => $header)
                        <th class="border-b border-l border-gray-200 p-2">
                            <select wire:model.live="mappings.{{ $index }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">-- Пропустити --</option>
                                @foreach ($mappingOptions as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($rawRows as $rowIndex => $row)
                    @php
                    $rowNumber = $rowIndex + 1;
                    $isSelectedRow = ($rowNumber == $headerRowNumber);
                    @endphp
                    <tr class="{{ $isSelectedRow ? 'bg-blue-200' : 'hover:bg-gray-100' }}">
                        <td class="p-2 border-r border-gray-200 text-center">
                            <input id="row-{{ $rowNumber }}" type="radio" wire:model.live="headerRowNumber" value="{{ $rowNumber }}" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        </td>
                        @foreach($row as $cellIndex => $cell)
                        @php
                        $isMapped = !empty($mappings[$cellIndex]);
                        // ## ЗМІНА: Логіка підсвітки тепер тут
                        $isHighlighted = $this->isCellHighlighted($cellIndex, $cell);
                        @endphp
                        <td class="p-2 text-sm text-gray-700 border-l border-gray-200 truncate 
                                        {{ $isMapped && !$isSelectedRow ? 'bg-blue-50' : '' }} 
                                        {{ $isHighlighted ? 'bg-yellow-100' : '' }}"
                            title="{{ $cell }}">
                            <label for="row-{{ $rowNumber }}" class="block w-full h-full cursor-pointer">{{ $cell }}</label>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <hr class="my-6">

        {{-- БЛОК ПРАВИЛ ФІЛЬТРАЦІЇ --}}
        <p class="block text-sm font-medium text-gray-700 mb-2">3. Налаштуйте правила фільтрації</p>
        <div class="space-y-4 p-4 border border-gray-200 rounded-lg">
            @forelse ($filters as $index => $filter)
            <div class="flex items-center space-x-2" wire:key="filter-{{ $index }}">
                <select wire:model.live="filters.{{ $index }}.columnIndex" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">-- Виберіть стовпець --</option>
                    @foreach ($this->resultHeaders as $colIndex => $headerName)
                    <option value="{{ $colIndex }}">{{ $headerName }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filters.{{ $index }}.condition" class="rounded-md border-gray-300 shadow-sm text-sm">
                    @foreach ($conditions as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
                @if(!in_array($filter['condition'], ['is_empty', 'is_not_empty']))
                <input type="text" wire:model.live.debounce.500ms="filters.{{ $index }}.value" class="rounded-md border-gray-300 shadow-sm text-sm flex-1">
                @endif
                <button wire:click="removeFilter({{ $index }})" class="text-red-500 hover:text-red-700">&times;</button>
            </div>
            @empty
            <p class="text-sm text-gray-500">Немає правил фільтрації.</p>
            @endforelse
            <button wire:click="addFilter" class="mt-2 px-3 py-1 bg-gray-200 text-gray-700 text-sm font-semibold rounded-md hover:bg-gray-300">+ Додати фільтр</button>
        </div>

        <hr class="my-6">

        {{-- ФІНАЛЬНА ТАБЛИЦЯ-РЕЗУЛЬТАТ --}}
        <p class="block text-sm font-medium text-gray-700 mb-2">4. Результат</p>
        <div class="overflow-x-auto border border-gray-300 rounded-lg">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        @foreach($this->resultHeaders as $header)
                        <th class="p-2 text-left text-sm font-semibold text-gray-600 border-b">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse($this->resultRows->take(20) as $row)
                    <tr>
                        @foreach($this->resultHeaders as $index => $header)
                        @php $cellValue = $row[$index] ?? ''; @endphp
                        {{-- ## ЗМІНА: Підсвітка звідси видалена --}}
                        <td class="p-2 border-t text-sm text-gray-700 truncate" title="{{ $cellValue }}">{{ $cellValue }}</td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($this->resultHeaders) }}" class="p-4 text-center text-gray-500">Немає даних, що відповідають вашим фільтрам.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <hr class="my-6">

        {{-- ## НОВИЙ БЛОК: Поля для збереження шаблону --}}
        <div class="p-4 border border-gray-200 rounded-lg space-y-4">
            <h3 class="text-lg font-semibold text-gray-800">5. Збереження шаблону</h3>
            <div>
                <label for="template-name" class="block text-sm font-medium text-gray-700">Назва шаблону</label>
                <input id="template-name" type="text" wire:model="templateName" placeholder="Наприклад: Звіт Укренерго (щомісячний)" class="mt-1 block w-full md:w-1/2 rounded-md border-gray-300 shadow-sm">
                @error('templateName') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="file-pattern" class="block text-sm font-medium text-gray-700">Ключова фраза з імені файлу для розпізнавання</label>
                <input id="file-pattern" type="text" wire:model="filePattern" placeholder="Наприклад: Укренерго" class="mt-1 block w-full md:w-1/2 rounded-md border-gray-300 shadow-sm">
                @error('filePattern') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
            @error('mappings') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6">
            <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700">Зберегти шаблон</button>
        </div>

        {{-- Повідомлення про успішне збереження --}}
        @if($successMessage)
        <div class="mt-4 p-4 bg-green-100 text-green-700 border border-green-200 rounded-md">
            {{ $successMessage }}
        </div>
        @endif

        @endif {{-- кінець @if ($rawRows->isNotEmpty()) --}}
    </div>
</div>