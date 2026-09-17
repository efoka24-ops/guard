<?php

namespace App\Modules\Endpoint\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElementQuarantaine extends Model
{
    use HasUuids;

    protected $table = 'elements_quarantaine';

    protected $fillable = [
        'evenement_scan_id',
        'raison',
        'statut',
        'resolu_par_utilisateur_id',
        'resolu_le',
    ];

    protected function casts(): array
    {
        return [
            'resolu_le' => 'datetime',
        ];
    }

    public function evenementScan(): BelongsTo
    {
        return $this->belongsTo(EvenementScan::class);
    }

    public function resoluParUtilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolu_par_utilisateur_id');
    }
}
