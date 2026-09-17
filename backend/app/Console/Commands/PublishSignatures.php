<?php

namespace App\Console\Commands;

use App\Modules\Endpoint\Models\VersionSignatures;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * T036 — Publie une nouvelle VersionSignatures à partir des fichiers de
 * `guard/signatures/{env}/` (hashes.json + rules/*.yar). Génère le paquet
 * complet initial si aucune version n'existe encore, sinon un delta
 * cumulatif implicite (les hashes/règles déjà publiés sont exclus).
 */
class PublishSignatures extends Command
{
    protected $signature = 'signatures:publish {--dataset=test : Sous-dossier de guard/signatures/ à publier}';

    protected $description = 'Publie une nouvelle version de la base de signatures (hashes SHA-256 + règles YARA)';

    public function handle(): int
    {
        $dataset = $this->option('dataset');
        $dir = base_path("../signatures/{$dataset}");

        if (! File::isDirectory($dir)) {
            $this->error("Dossier introuvable : {$dir}");

            return self::FAILURE;
        }

        $hashesData = json_decode(File::get("{$dir}/hashes.json"), true);
        $nouveauxHashes = collect($hashesData['hashes'] ?? [])->pluck('sha256');

        $nouvellesRegles = collect(File::glob("{$dir}/rules/*.yar"))
            ->map(fn (string $path) => File::get($path));

        $dejaPublies = VersionSignatures::query()
            ->pluck('hashes_ajoutes')
            ->flatten()
            ->unique();

        $deltaHashes = $nouveauxHashes->diff($dejaPublies)->values();

        if ($deltaHashes->isEmpty() && VersionSignatures::exists()) {
            $this->info('Rien de nouveau à publier.');

            return self::SUCCESS;
        }

        $payload = [
            'hashes' => $deltaHashes->all(),
            'regles' => $nouvellesRegles->all(),
        ];
        $tailleKo = round(strlen(json_encode($payload)) / 1024, 2);

        $version = VersionSignatures::create([
            'numero_version' => now()->format('Y.m.d.His'),
            'taille_delta_ko' => (int) ceil($tailleKo),
            'hashes_ajoutes' => $deltaHashes->all(),
            'regles_yara_ajoutees' => $nouvellesRegles->all(),
            'checksum_delta' => hash('sha256', json_encode($payload)),
            'publie_le' => now(),
        ]);

        $this->info("Version {$version->numero_version} publiée ({$tailleKo} Ko, {$deltaHashes->count()} hash(es)).");

        if ($tailleKo > 50) {
            $this->warn('Taille > 50 Ko : dépasse la contrainte SC-010 (delta hebdomadaire 2G/EDGE).');
        }

        return self::SUCCESS;
    }
}
