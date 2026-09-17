<?php

namespace App\Modules\Endpoint\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EvenementScan extends Model
{
    use HasUuids;

    protected $table = 'evenements_scan';

    protected $fillable = [
        'terminal_id',
        'client_event_id',
        'declencheur',
        'hash_sha256',
        'niveau_max_atteint',
        'regle_yara_correspondante',
        'score_confiance_ia',
        'classification',
        'action_prise',
        'survenu_le',
        'synchronise_le',
    ];

    protected function casts(): array
    {
        return [
            'survenu_le' => 'datetime',
            'synchronise_le' => 'datetime',
        ];
    }

    /** Classifications qui doivent déclencher la création d'une Alerte (cf. CreerAlerteDepuisScan). */
    public function estAlertant(): bool
    {
        return in_array($this->classification, ['probable_malware', 'malware_confirme'], true);
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(Terminal::class);
    }

    public function elementQuarantaine(): HasOne
    {
        return $this->hasOne(ElementQuarantaine::class);
    }
}
