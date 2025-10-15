<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParsingTemplate extends Model
{
    use HasFactory;

    /**
     * Поля, які можна заповнювати масово.
     */
    protected $fillable = [
        'name',
        'file_pattern',
        'config',
    ];

    /**
     * Автоматичне перетворення типів.
     */
    protected $casts = [
        'config' => 'array',
    ];
}
