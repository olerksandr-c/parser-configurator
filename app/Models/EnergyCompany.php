<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnergyCompany extends Model
{
    use HasFactory;

    /**
     * Атрибути, які можна масово призначати.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'u_code',
    ];
}