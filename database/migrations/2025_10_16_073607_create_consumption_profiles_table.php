<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Запуск міграцій.
     */
    public function up(): void
    {
        Schema::create('consumption_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('energy_company_id')->constrained()->onDelete('cascade');
            $table->date('profile_date');
            $table->unsignedTinyInteger('version')->default(1);

            // Створюємо 25 колонок (h1, h2, ..., h25) для погодинних коефіцієнтів
            // Поле h25 буде використовуватись тільки в дні переходу на зимовий час
            for ($i = 1; $i <= 25; $i++) {
                $table->decimal('h' . $i, 12, 8)->default(0);
            }

            $table->timestamps();

            $table->unique(['energy_company_id', 'profile_date', 'version']);
        });
    }

    /**
     * Відкат міграцій.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumption_profiles');
    }
};