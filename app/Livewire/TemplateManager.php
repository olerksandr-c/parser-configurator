<?php

namespace App\Livewire;

use App\Models\ParsingTemplate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Менеджер шаблонів')]
class TemplateManager extends Component
{


    public bool $showEditModal = false;
    public ?ParsingTemplate $editingTemplate = null;
    public string $editingName = '';
    public string $editingPattern = '';

    /**
     * Відкриває модальне вікно та завантажує дані для редагування.
     */
    public function edit(int $templateId): void
    {
        $this->editingTemplate = ParsingTemplate::findOrFail($templateId);
        $this->editingName = $this->editingTemplate->name;
        $this->editingPattern = $this->editingTemplate->file_pattern;
        $this->showEditModal = true;
    }

    /**
     * Оновлює дані шаблону.
     */
    public function update(): void
    {
        $this->validate([
            'editingName' => [
                'required',
                'string',
                'min:3',
                Rule::unique('parsing_templates', 'name')->ignore($this->editingTemplate->id)
            ],
            'editingPattern' => 'required|string|min:3',
        ]);

        $this->editingTemplate->update([
            'name' => $this->editingName,
            'file_pattern' => $this->editingPattern,
        ]);

        $this->closeModal();
        session()->flash('status', 'Дані шаблону успішно оновлено.');
    }

    /**
     * Закриває та скидає стан модального вікна.
     */
    public function closeModal(): void
    {
        $this->showEditModal = false;
        $this->reset(['editingTemplate', 'editingName', 'editingPattern']);
    }


    /**
     * Видаляє шаблон за його ID.
     * Повідомлення для підтвердження вбудовано в Blade-файл.
     */
    public function delete(int $templateId): void
    {
        $template = ParsingTemplate::find($templateId);
        if ($template) {
            $template->delete();
            session()->flash('status', 'Шаблон успішно видалено.');
        }
    }

    public function render()
    {
        $templates = ParsingTemplate::latest()->get();
        return view('livewire.template-manager', [
            'templates' => $templates,
        ]);
    }
}
