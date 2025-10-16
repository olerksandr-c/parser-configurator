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
        Schema::create('energy_companies', function (Blueprint $table) {
            // Створюємо автоінкрементний первинний ключ 'id'
            $table->id();
            // Створюємо поле для назви компанії
            $table->string('name');
            // Створюємо унікальне поле для У-коду
            $table->string('u_code')->unique();
            // Створюємо стандартні поля 'created_at' та 'updated_at'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('energy_companies');
    }
};
