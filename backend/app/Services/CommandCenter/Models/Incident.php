<?php

namespace App\Services\CommandCenter\Models;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    use HasUuids;

    protected $fillable = [
        'organisation_id',
        'titre',
        'type',
        'statut',
        'dossier_legal_genere',
        'dossier_legal_url',
        'pays_autorite_cible',
        'ouvert_le',
        'cloture_le',
    ];

    protected function casts(): array
    {
        return [
            'dossier_legal_genere' => 'boolean',
            'ouvert_le' => 'datetime',
            'cloture_le' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function alertes(): HasMany
    {
        return $this->hasMany(Alerte::class);
    }
}
