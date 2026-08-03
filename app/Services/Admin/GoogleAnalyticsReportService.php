<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GoogleAnalyticsReportService
{
    /**
     * @return array{
     *   configured: bool,
     *   mode: string,
     *   measurement_id: string,
     *   embed_url: string|null,
     *   labels: list<string>,
     *   active_users: list<int>,
     *   sessions: list<int>,
     *   page_views: list<int>,
     *   totals: array{active_users: int, sessions: int, page_views: int},
     *   error: string|null
     * }
     */
    public function dashboard(int $days = 7): array
    {
        $measurementId = (string) config('services.google.measurement_id', '');
        $embedUrl = trim((string) config('services.google.embed_url', ''));
        $propertyId = trim((string) config('services.google.property_id', ''));
        $credentialsPath = (string) config('services.google.credentials_path', '');

        $base = [
            'configured' => false,
            'mode' => 'none',
            'measurement_id' => $measurementId,
            'embed_url' => $embedUrl !== '' ? $embedUrl : null,
            'labels' => [],
            'active_users' => [],
            'sessions' => [],
            'page_views' => [],
            'totals' => [
                'active_users' => 0,
                'sessions' => 0,
                'page_views' => 0,
            ],
            'error' => null,
        ];

        if ($embedUrl !== '') {
            $base['configured'] = true;
            $base['mode'] = 'embed';

            return $base;
        }

        if ($propertyId === '' || $credentialsPath === '' || ! is_file($credentialsPath)) {
            $base['error'] = 'لإظهار شارت غوغل داخل الأدمن: ضع GA_PROPERTY_ID ومسار ملف الخدمة GA_CREDENTIALS_JSON، أو رابط تضمين Looker Studio في GA_EMBED_URL.';

            return $base;
        }

        try {
            $cacheKey = 'admin.ga.report.'.$propertyId.'.'.$days;
            $payload = Cache::remember($cacheKey, 300, function () use ($propertyId, $credentialsPath, $days) {
                return $this->fetchReport($propertyId, $credentialsPath, $days);
            });

            return array_merge($base, $payload, [
                'configured' => true,
                'mode' => 'api',
                'error' => null,
            ]);
        } catch (Throwable $e) {
            Log::warning('admin.ga.report_failed', ['message' => $e->getMessage()]);
            $base['error'] = $e->getMessage();

            return $base;
        }
    }

    /**
     * @return array{
     *   labels: list<string>,
     *   active_users: list<int>,
     *   sessions: list<int>,
     *   page_views: list<int>,
     *   totals: array{active_users: int, sessions: int, page_views: int}
     * }
     */
    private function fetchReport(string $propertyId, string $credentialsPath, int $days): array
    {
        $token = $this->accessToken($credentialsPath);
        $property = str_starts_with($propertyId, 'properties/')
            ? $propertyId
            : 'properties/'.$propertyId;

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->post('https://analyticsdata.googleapis.com/v1beta/'.$property.':runReport', [
                'dimensions' => [
                    ['name' => 'date'],
                ],
                'metrics' => [
                    ['name' => 'activeUsers'],
                    ['name' => 'sessions'],
                    ['name' => 'screenPageViews'],
                ],
                'dateRanges' => [
                    [
                        'startDate' => $days.'daysAgo',
                        'endDate' => 'today',
                    ],
                ],
                'orderBys' => [
                    [
                        'dimension' => ['dimensionName' => 'date'],
                        'desc' => false,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('GA4 API error: '.$response->body());
        }

        $rows = $response->json('rows') ?? [];
        $labels = [];
        $activeUsers = [];
        $sessions = [];
        $pageViews = [];

        foreach ($rows as $row) {
            $dateRaw = (string) ($row['dimensionValues'][0]['value'] ?? '');
            $labels[] = $this->formatDateLabel($dateRaw);
            $activeUsers[] = (int) ($row['metricValues'][0]['value'] ?? 0);
            $sessions[] = (int) ($row['metricValues'][1]['value'] ?? 0);
            $pageViews[] = (int) ($row['metricValues'][2]['value'] ?? 0);
        }

        return [
            'labels' => $labels,
            'active_users' => $activeUsers,
            'sessions' => $sessions,
            'page_views' => $pageViews,
            'totals' => [
                'active_users' => array_sum($activeUsers),
                'sessions' => array_sum($sessions),
                'page_views' => array_sum($pageViews),
            ],
        ];
    }

    private function accessToken(string $credentialsPath): string
    {
        $json = json_decode((string) file_get_contents($credentialsPath), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            throw new RuntimeException('ملف اعتماد Google غير صالح.');
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->base64UrlEncode(json_encode([
            'iss' => $json['client_email'],
            'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsigned = $header.'.'.$claim;
        $key = openssl_pkey_get_private($json['private_key']);
        if ($key === false) {
            throw new RuntimeException('تعذر قراءة المفتاح الخاص لخدمة Google.');
        }

        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('فشل توقيع JWT لـ Google.');
        }

        $jwt = $unsigned.'.'.$this->base64UrlEncode($signature);

        $tokenResponse = Http::asForm()
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (! $tokenResponse->successful() || ! $tokenResponse->json('access_token')) {
            throw new RuntimeException('تعذر الحصول على توكن Google: '.$tokenResponse->body());
        }

        return (string) $tokenResponse->json('access_token');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function formatDateLabel(string $yyyymmdd): string
    {
        if (strlen($yyyymmdd) !== 8) {
            return $yyyymmdd;
        }

        return substr($yyyymmdd, 6, 2).'/'.substr($yyyymmdd, 4, 2);
    }
}
