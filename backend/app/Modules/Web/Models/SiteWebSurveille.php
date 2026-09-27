<?php

namespace App\Modules\Web\Models;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** T062 */
class SiteWebSurveille extends Model
{
    use HasUuids;

    protected $table = 'sites_web_surveilles';

    protected $fillable = [
        'organisation_id',
        'url',
        'hash_page_accueil',
        'derniere_verification_le',
        'statut_ssl',
        'ssl_expiration_le',
        'statut',
    ];

    protected $casts = [
        'derniere_verification_le' => 'datetime',
        'ssl_expiration_le' => 'date',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function evenementsWeb(): HasMany
    {
        return $this->hasMany(EvenementWeb::class, 'site_web_id');
    }

    public function scopeParOrganisation(Builder $query, string $organisationId): Builder
    {
        return $query->where('organisation_id', $organisationId);
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('statut', 'actif');
    }
}
