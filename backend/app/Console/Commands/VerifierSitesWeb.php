<?php

namespace App\Console\Commands;

use App\Modules\Web\Jobs\VerifierDefacementSite;
use App\Modules\Web\Jobs\VerifierEnTetesSite;
use App\Modules\Web\Jobs\VerifierSslSite;
use App\Modules\Web\Models\SiteWebSurveille;
use Illuminate\Console\Command;

/**
 * T070/T074/T080 — Dispatche un job de vérification (par type) pour chaque
 * site actif. `type` sélectionne quel job planifier, cf. Console/Kernel.php
 * (fréquences distinctes : défacement toutes les 2 min, en-têtes hebdo, SSL
 * quotidien).
 */
class VerifierSitesWeb extends Command
{
    protected $signature = 'web:verifier {type : defacements|en-tetes|ssl}';

    protected $description = 'Dispatche la vérification GUARD WEB (défacement, en-têtes, SSL) pour tous les sites actifs';

    public function handle(): int
    {
        $type = $this->argument('type');

        $job = match ($type) {
            'defacements' => VerifierDefacementSite::class,
            'en-tetes' => VerifierEnTetesSite::class,
            'ssl' => VerifierSslSite::class,
            default => null,
        };

        if ($job === null) {
            $this->error("Type inconnu : {$type}");

            return self::FAILURE;
        }

        $compte = 0;

        SiteWebSurveille::actifs()->each(function (SiteWebSurveille $site) use ($job, &$compte) {
            $job::dispatch($site->id);
            $compte++;
        });

        $this->info("{$compte} site(s) mis en file pour '{$type}'.");

        return self::SUCCESS;
    }
}
