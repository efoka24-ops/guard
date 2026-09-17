<?php

namespace App\Modules\Endpoint\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class VersionSignatures extends Model
{
    use HasUuids;

    protected $table = 'versions_signatures';

    protected $fillable = [
        'numero_version',
        'taille_delta_ko',
        'hashes_ajoutes',
        'regles_yara_ajoutees',
        'checksum_delta',
        'publie_le',
    ];

    protected function casts(): array
    {
        return [
            'hashes_ajoutes' => 'array',
            'regles_yara_ajoutees' => 'array',
            'publie_le' => 'datetime',
        ];
    }

    /** Dernière version publiée (utile pour le paquet complet initial, sans from_version). */
    public static function derniere(): ?self
    {
        return static::orderByDesc('publie_le')->first();
    }

    /** Toutes les versions plus récentes que $numeroVersion, pour construire un delta cumulé. */
    public static function depuis(?string $numeroVersion): Collection
    {
        $query = static::orderBy('publie_le');

        if ($numeroVersion !== null) {
            $reference = static::where('numero_version', $numeroVersion)->first();
            if ($reference) {
                $query->where('publie_le', '>', $reference->publie_le);
            }
        }

        return $query->get();
    }
}
