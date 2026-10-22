<?php

namespace App\Models;

use Database\Factories\VaccinationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaccination extends Model
{
    use HasFactory;

    protected $fillable = ["pet_id", "vaccine_name", "date_administered", "next_due_date", "notes"];

    protected function casts(): array
    {
        return ['date_administered' => 'date', 'next_due_date' => 'date'];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    protected static function newFactory(): VaccinationFactory
    {
        return VaccinationFactory::new();
    }
}