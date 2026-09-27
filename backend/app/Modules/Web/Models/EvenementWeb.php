<?php

namespace App\Modules\Web\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** T063 */
class EvenementWeb extends Model
{
    use HasUuids;

    protected $table = 'evenements_web';

    protected $fillable = [
        'site_web_id',
        'type',
        'detail',
        'detecte_le',
    ];

    protected $casts = [
        'detail' => 'array',
        'detecte_le' => 'datetime',
    ];

    public function siteWeb(): BelongsTo
    {
        return $this->belongsTo(SiteWebSurveille::class, 'site_web_id');
    }

    public function estAlertant(): bool
    {
        return in_array($this->type, ['defacement', 'ssl_expiration_proche', 'ssl_expire'], true);
    }
}
