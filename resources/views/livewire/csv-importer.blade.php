<div>
    <div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Імпорт профілів споживання</h2>

        {{-- Повідомлення про успіх або помилку --}}
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Форма завантаження --}}
        <form wire:submit.prevent="import">
            @csrf
            <div class="space-y-4">
                {{-- Поле для версії --}}
                <div>
                    <label for="version" class="block text-sm font-medium text-gray-700">Версія профілю</label>
                    <input type="number" id="version" wire:model.defer="version" min="1"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('version') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Поле для завантаження файлу --}}
                <div>
                    <label for="csvFile" class="block text-sm font-medium text-gray-700">CSV Файл</label>
                    <input type="file" id="csvFile" wire:model="csvFile"
                           class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                    @error('csvFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Кнопка та індикатор завантаження --}}
            <div class="mt-6 flex items-center">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="import">Імпортувати</span>
                    <span wire:loading wire:target="import">Обробка...</span>
                </button>
                <div wire:loading wire:target="csvFile" class="ml-4 text-sm text-gray-600">
                    Завантаження файлу...
                </div>
            </div>
        </form>
    </div>
</div>