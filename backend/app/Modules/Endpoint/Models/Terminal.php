<?php

namespace App\Modules\Endpoint\Models;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Terminal extends Model
{
    use HasUuids;

    protected $table = 'terminaux';

    protected $fillable = [
        'organisation_id',
        'plateforme',
        'identifiant_appareil',
        'token_acces_hash',
        'version_app',
        'version_signatures',
        'derniere_synchro_le',
        'score_risque_comportemental',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'derniere_synchro_le' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function evenementsScan(): HasMany
    {
        return $this->hasMany(EvenementScan::class);
    }
}
