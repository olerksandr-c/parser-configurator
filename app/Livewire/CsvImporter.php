<?php

namespace App\Livewire;

use App\Actions\ProcessCsvImportAction;
use Livewire\Component;
use Livewire\WithFileUploads;

class CsvImporter extends Component
{
    use WithFileUploads;

    /** @var \Illuminate\Http\UploadedFile|null */
    public $csvFile;

    /** @var int */
    public $version = 1;

    /**
     * Правила валідації для форми.
     */
    protected $rules = [
        'csvFile' => 'required|file|mimes:csv,txt',
        'version' => 'required|integer|min:1',
    ];

    /**
     * Метод для обробки відправки форми.
     */
    public function import(ProcessCsvImportAction $importer)
    {
        // Валідація вхідних даних
        $this->validate();

        try {
            // Зберігаємо файл тимчасово та передаємо шлях до обробника
            $filePath = $this->csvFile->getRealPath();
            $importer->execute($filePath, $this->version);

            // Показуємо повідомлення про успіх
            session()->flash('success', 'Файл успішно оброблено та дані імпортовано!');
            
            // Очищуємо поле файлу після успішного завантаження
            $this->reset('csvFile');

        } catch (\Exception $e) {
            // У випадку помилки, показуємо її користувачу
            session()->flash('error', 'Помилка: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.csv-importer');
    }
}