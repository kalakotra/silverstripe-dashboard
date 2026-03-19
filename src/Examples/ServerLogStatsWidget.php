<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Examples;

use Kalakotra\Dashboard\Widgets\StatsWidget;
use Kalakotra\Dashboard\Widgets\WidgetWidth;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * ServerLogStatsWidget
 *
 * Parses server access logs and displays high-level traffic KPIs.
 *
 * Supports Apache/Nginx common or combined log format.
 */
class ServerLogStatsWidget extends StatsWidget
{
    protected string $title = 'Server Traffic (Access Log)';

    protected int $order = 1;

    protected WidgetWidth $width = WidgetWidth::Half;

    protected int $cacheLifetime = 120;

    /**
     * Candidate log paths.
     *
     * You can override via YAML config:
     *   Kalakotra\Dashboard\Examples\ServerLogStatsWidget:
     *     log_paths:
     *       - '/var/log/nginx/access.log'
     */
    private static $log_paths = [
        '/var/log/nginx/access.log',
        '/var/log/apache2/access.log',
        '/var/log/httpd/access_log',
    ];

    /**
     * Number of newest log entries to parse.
     */
    private static $max_lines = 5000;

    public function canView(Member $member): bool
    {
        return Permission::checkMember($member, 'ADMIN');
    }

    public function getStats(): array
    {
        $logPath = $this->resolveLogPath();

        if ($logPath === null) {
            return [
                [
                    'label' => 'Access Log',
                    'value' => 'Not found',
                    'icon'  => 'font-icon-warning',
                    'color' => 'orange',
                    'delta' => 'Set DASHBOARD_ACCESS_LOG or log_paths',
                    'trend' => 'neutral',
                ],
            ];
        }

        $stats = $this->collectStats($logPath);
        $errorCount = $stats['status4xx'] + $stats['status5xx'];
        $topPath = $stats['topPath'];
        $topHits = $stats['topPathHits'];

        return [
            [
                'label' => 'Requests',
                'value' => number_format($stats['requests']),
                'icon'  => 'font-icon-globe-1',
                'color' => 'blue',
                'delta' => 'Last ' . number_format($stats['sampleSize']) . ' log lines',
                'trend' => 'neutral',
            ],
            [
                'label' => 'Unique IPs',
                'value' => number_format($stats['uniqueIps']),
                'icon'  => 'font-icon-network',
                'color' => 'green',
                'delta' => basename($logPath),
                'trend' => 'neutral',
            ],
            [
                'label' => 'Top URL',
                'value' => $topPath,
                'icon'  => 'font-icon-link',
                'color' => 'purple',
                'delta' => $topHits > 0 ? (string) $topHits . ' hits' : 'No parsed requests',
                'trend' => 'neutral',
            ],
            [
                'label' => 'Errors (4xx/5xx)',
                'value' => number_format($errorCount),
                'icon'  => 'font-icon-attention',
                'color' => $errorCount > 0 ? 'red' : 'green',
                'delta' => sprintf('2xx:%d 3xx:%d 4xx:%d 5xx:%d', $stats['status2xx'], $stats['status3xx'], $stats['status4xx'], $stats['status5xx']),
                'trend' => $errorCount > 0 ? 'up' : 'neutral',
            ],
        ];
    }

    private function resolveLogPath(): ?string
    {
        $envPath = Environment::getEnv('DASHBOARD_ACCESS_LOG');
        if (is_string($envPath) && $envPath !== '' && is_readable($envPath)) {
            return $envPath;
        }

        $paths = (array) $this->config()->get('log_paths');

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<string, int|string>
     */
    private function collectStats(string $logPath): array
    {
        $maxLines = (int) $this->config()->get('max_lines');
        if ($maxLines < 1) {
            $maxLines = 5000;
        }

        $lines = $this->tailLines($logPath, $maxLines);

        $requests = 0;
        $uniqueIps = [];
        $paths = [];
        $status2xx = 0;
        $status3xx = 0;
        $status4xx = 0;
        $status5xx = 0;

        foreach ($lines as $line) {
            $parsed = $this->parseLogLine($line);
            if ($parsed === null) {
                continue;
            }

            $requests++;
            $uniqueIps[$parsed['ip']] = true;

            $path = $this->normalisePath($parsed['url']);
            if ($path !== '') {
                $paths[$path] = ($paths[$path] ?? 0) + 1;
            }

            $status = (int) $parsed['status'];
            if ($status >= 500) {
                $status5xx++;
            } elseif ($status >= 400) {
                $status4xx++;
            } elseif ($status >= 300) {
                $status3xx++;
            } elseif ($status >= 200) {
                $status2xx++;
            }
        }

        arsort($paths);
        $topPath = (string) array_key_first($paths);
        $topHits = $topPath !== '' ? (int) ($paths[$topPath] ?? 0) : 0;

        if ($topPath === '') {
            $topPath = 'n/a';
        }

        return [
            'requests'   => $requests,
            'uniqueIps'  => count($uniqueIps),
            'topPath'    => $this->truncate($topPath, 28),
            'topPathHits'=> $topHits,
            'status2xx'  => $status2xx,
            'status3xx'  => $status3xx,
            'status4xx'  => $status4xx,
            'status5xx'  => $status5xx,
            'sampleSize' => count($lines),
        ];
    }

    /**
     * Reads only the newest lines in memory using a fixed-size ring buffer.
     *
     * @return array<int, string>
     */
    private function tailLines(string $path, int $maxLines): array
    {
        $buffer = [];
        $index = 0;

        try {
            $file = new \SplFileObject($path, 'r');

            while (!$file->eof()) {
                $line = trim((string) $file->fgets());
                if ($line === '') {
                    continue;
                }

                if (count($buffer) < $maxLines) {
                    $buffer[] = $line;
                    continue;
                }

                $buffer[$index] = $line;
                $index = ($index + 1) % $maxLines;
            }
        } catch (\RuntimeException) {
            return [];
        }

        if (count($buffer) < $maxLines || $index === 0) {
            return $buffer;
        }

        return array_merge(
            array_slice($buffer, $index),
            array_slice($buffer, 0, $index)
        );
    }

    /**
     * @return array{ip: string, url: string, status: string}|null
     */
    private function parseLogLine(string $line): ?array
    {
        $pattern = '/^(?<ip>\S+)\s+\S+\s+\S+\s+\[[^\]]+\]\s+"(?<method>[A-Z]+)\s+(?<url>[^\s"]+)\s+HTTP\/[0-9.]+"\s+(?<status>\d{3})\s+\S+/';

        if (!preg_match($pattern, $line, $m)) {
            return null;
        }

        return [
            'ip'     => $m['ip'],
            'url'    => $m['url'],
            'status' => $m['status'],
        ];
    }

    private function normalisePath(string $url): string
    {
        if ($url === '' || $url === '-') {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }

        return $path;
    }

    private function truncate(string $value, int $maxLength): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength - 3) . '...';
    }
}