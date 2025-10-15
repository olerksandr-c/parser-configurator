<div>
    <div class="p-8 bg-white shadow-md rounded-lg">
        <h2 class="text-2xl font-bold mb-4">Обробка файлів</h2>

        <form wire:submit.prevent="process">
            <div class="mb-4">
                <label for="files-upload" class="block text-sm font-medium text-gray-700">1. Виберіть один або декілька файлів для обробки</label>
                <input id="files-upload" type="file" wire:model="uploadedFiles" multiple class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100"/>
                @error('uploadedFiles.*') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700" wire:loading.attr="disabled">
                <span wire:loading.remove>Обробити</span>
                <span wire:loading>Обробка...</span>
            </button>
        </form>

        @if($processingFinished)
            <hr class="my-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Результати обробки</h3>
            @if (empty($statusLog))
                <p>Не було завантажено файлів.</p>
            @else
                <p class="mb-4 text-sm text-gray-600">
                    Обробку завершено. Якщо завантаження звіту не почалось автоматично, перевірте, чи не заблокував його ваш браузер.
                </p>
                <table class="min-w-full border-collapse border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 text-left text-sm font-semibold text-gray-600 border-b">Файл</th>
                            <th class="p-2 text-left text-sm font-semibold text-gray-600 border-b">Шаблон</th>
                            <th class="p-2 text-left text-sm font-semibold text-gray-600 border-b">Статус</th>
                            <th class="p-2 text-left text-sm font-semibold text-gray-600 border-b">Знайдено рядків</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($statusLog as $logEntry)
                        <tr>
                            <td class="p-2 border-t text-sm">{{ $logEntry[0] }}</td>
                            <td class="p-2 border-t text-sm">{{ $logEntry[1] }}</td>
                            <td class="p-2 border-t text-sm">{{ $logEntry[2] }}</td>
                            <td class="p-2 border-t text-sm">{{ $logEntry[3] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    </div>
</div>