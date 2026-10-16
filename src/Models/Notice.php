<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vipertecpro\DataRightsDesk\Support\Markdown;

/**
 * A published notice (s.5, Rule 3). One row per version and locale.
 *
 * The body is the Markdown that was shown; hash is sha256(body). The version
 * and publication date sit in their own columns and are added as a header by
 * document(), so an unchanged body keeps its hash between versions.
 *
 * @property string $id
 * @property int $version
 * @property string $locale
 * @property string $body
 * @property string $hash
 * @property array<string, mixed> $purposes
 * @property CarbonInterface $published_at
 */
class Notice extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $table = 'data_rights_notices';

    /** @var list<string> */
    protected $fillable = [
        'version',
        'locale',
        'body',
        'hash',
        'purposes',
        'published_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'purposes' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /** @return HasMany<NoticeDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NoticeDelivery::class, 'notice_id');
    }

    /** The full standalone Markdown document: version and date header, then the body. */
    public function document(): string
    {
        return Markdown::document($this->body, Markdown::noticeHeader($this->version, $this->published_at, $this->locale));
    }

    public function html(): string
    {
        return Markdown::toHtml($this->document());
    }

    public function hashMatches(): bool
    {
        return hash_equals($this->hash, hash('sha256', $this->body));
    }
}
