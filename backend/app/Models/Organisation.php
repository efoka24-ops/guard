<?php

namespace App\Models;

use App\Services\CommandCenter\Models\Alerte;
use App\Services\CommandCenter\Models\Incident;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisation extends Model
{
    use HasUuids;

    protected $fillable = [
        'nom',
        'pays',
        'modules_actifs',
        'score_securite_global',
        'mode_msp',
    ];

    protected function casts(): array
    {
        return [
            'modules_actifs' => 'array',
            'mode_msp' => 'boolean',
        ];
    }

    public function utilisateurs(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function alertes(): HasMany
    {
        return $this->hasMany(Alerte::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
