<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parsing_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Унікальна назва шаблону, яку дає менеджер
            $table->string('file_pattern'); // Ключова фраза для розпізнавання файлу
            $table->json('config'); // Всі налаштування (мапінг, фільтри) у форматі JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parsing_templates');
    }
};
