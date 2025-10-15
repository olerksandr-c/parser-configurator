<div>
    <div class="p-8 bg-white shadow-md rounded-lg">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Менеджер шаблонів</h2>
            <a href="{{ url('parser-configurator') }}" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700">
                + Створити новий шаблон
            </a>
        </div>

        @if (session('status'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 border border-green-200 rounded-md">
            {{ session('status') }}
        </div>
        @endif

        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left text-sm font-semibold text-gray-600">Назва</th>
                        <th class="p-3 text-left text-sm font-semibold text-gray-600">Ключова фраза</th>
                        <th class="p-3 text-left text-sm font-semibold text-gray-600">Дії</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse ($templates as $template)
                    <tr class="hover:bg-gray-50">
                        <td class="p-3 border-t">{{ $template->name }}</td>
                        <td class="p-3 border-t font-mono text-sm bg-gray-50">{{ $template->file_pattern }}</td>
                        <td class="p-3 border-t space-x-3">
                            {{-- ## ЗМІНЕНО: Нові кнопки --}}
                            <button wire:click="edit({{ $template->id }})" class="text-indigo-600 hover:underline">Змінити назву</button>
                            <a href="{{ url('parser-configurator/' . $template->id) }}" class="text-blue-600 hover:underline">Редагувати правила</a>
                            <button
                                wire:click="delete({{ $template->id }})"
                                wire:confirm="Ви впевнені, що хочете видалити шаблон '{{ $template->name }}'?"
                                class="text-red-600 hover:underline">
                                Видалити
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="p-4 text-center text-gray-500">Ще не створено жодного шаблону.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ## НОВИЙ БЛОК: Модальне вікно для редагування --}}
    @if($showEditModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-lg">
            <h3 class="text-lg font-bold mb-4">Редагувати дані шаблону</h3>
            <div class="space-y-4">
                <div>
                    <label for="editing-name" class="block text-sm font-medium text-gray-700">Назва шаблону</label>
                    <input id="editing-name" type="text" wire:model="editingName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('editingName') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="editing-pattern" class="block text-sm font-medium text-gray-700">Ключова фраза</label>
                    <input id="editing-pattern" type="text" wire:model="editingPattern" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('editingPattern') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-4">
                <button wire:click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Скасувати</button>
                <button wire:click="update" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700">
                    <span wire:loading.remove wire:target="update">Зберегти зміни</span>
                    <span wire:loading wire:target="update">Збереження...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>