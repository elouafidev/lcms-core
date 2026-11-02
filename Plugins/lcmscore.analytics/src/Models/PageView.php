<?php

declare(strict_types=1);

namespace Plugins\Analytics\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Plugins\Analytics\Services\UserAgentParser;

/**
 * @property int $id
 * @property string $path
 * @property string $visitor_hash
 * @property ?string $referrer
 * @property ?string $referrer_host
 * @property string $device
 * @property string $browser
 * @property int $status_code
 * @property CarbonInterface $created_at
 *
 * @method static Builder<static> where(string|array<string, mixed>|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static Builder<static> create(array<string, mixed> $attributes = [])
 * @method static Builder<static> latest(string $column = 'created_at')
 * @method static static|null find(mixed $id, array<string> $columns = ['*'])
 */
class PageView extends Model
{
    /**
     * Disable updated_at timestamp since pageviews are immutable.
     */
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'plugin_analytics_pageviews';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'path',
        'visitor_hash',
        'referrer',
        'referrer_host',
        'device',
        'browser',
        'status_code',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Generate anonymous daily visitor hash (100% GDPR/CNIL compliant, zero cookies).
     */
    public static function generateVisitorHash(string $ip, string $userAgent): string
    {
        $salt = (string) config('app.key', 'lcms-analytics-salt');
        $date = now()->format('Y-m-d');

        return hash('sha256', $ip.'|'.$userAgent.'|'.$salt.'|'.$date);
    }

    /**
     * Record a pageview from incoming HTTP request.
     */
    public static function record(Request $request, int $statusCode = 200): self
    {
        $path = '/'.ltrim($request->path(), '/');
        $ip = (string) ($request->ip() ?? '127.0.0.1');
        $userAgent = (string) ($request->userAgent() ?? '');
        $referrer = $request->headers->get('referer');

        $device = UserAgentParser::detectDevice($userAgent);
        $browser = UserAgentParser::detectBrowser($userAgent);
        $referrerHost = UserAgentParser::extractReferrerHost($referrer, (string) config('app.url'));
        $visitorHash = static::generateVisitorHash($ip, $userAgent);

        /** @var self $pageview */
        $pageview = static::create([
            'path' => mb_substr($path, 0, 500),
            'visitor_hash' => $visitorHash,
            'referrer' => $referrer !== null ? mb_substr($referrer, 0, 500) : null,
            'referrer_host' => $referrerHost,
            'device' => $device,
            'browser' => $browser,
            'status_code' => $statusCode,
            'created_at' => now(),
        ]);

        return $pageview;
    }
}
