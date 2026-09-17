<?php

namespace App\Services\CommandCenter\Models;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerte extends Model
{
    use HasUuids;

    protected $table = 'alertes';

    protected $fillable = [
        'organisation_id',
        'module_source',
        'terminal_id',
        'incident_id',
        'niveau_criticite',
        'titre',
        'description_technique',
        'statut',
        'sla_echeance_le',
        'assignee_utilisateur_id',
    ];

    protected function casts(): array
    {
        return [
            'sla_echeance_le' => 'datetime',
        ];
    }

    /** Calcule l'échéance SLA selon le niveau de criticité (FR-024). */
    public static function slaPour(string $niveauCriticite): \DateTimeInterface
    {
        return match ($niveauCriticite) {
            'critique' => now()->addHours(24),
            'eleve' => now()->addHours(72),
            default => now()->addDays(7),
        };
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function assigneeUtilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_utilisateur_id');
    }
}
