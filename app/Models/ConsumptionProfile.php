<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumptionProfile extends Model
{
    use HasFactory;

    /**
     * Атрибути, які можна масово призначати.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'energy_company_id',
        'profile_date',
        'version',
        // Додаємо всі 25 полів годин для масового заповнення
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'h7',
        'h8',
        'h9',
        'h10',
        'h11',
        'h12',
        'h13',
        'h14',
        'h15',
        'h16',
        'h17',
        'h18',
        'h19',
        'h20',
        'h21',
        'h22',
        'h23',
        'h24',
        'h25',
    ];

    /**
     * Атрибути, які мають бути перетворені до нативних типів.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'profile_date' => 'date',
    ];

    /**
     * Отримати енергетичну компанію, до якої належить профіль.
     */
    public function energyCompany(): BelongsTo
    {
        return $this->belongsTo(EnergyCompany::class);
    }
}
