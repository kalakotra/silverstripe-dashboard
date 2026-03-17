<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

use SilverStripe\Security\Member;

/**
 * StatsWidget
 *
 * Displays a grid of KPI / statistic tiles.
 *
 * Each stat is an associative array:
 *   [
 *     'label'  => 'Total Users',
 *     'value'  => '1,234',
 *     'delta'  => '+12%',        // optional trend indicator
 *     'trend'  => 'up',          // 'up' | 'down' | 'neutral'
 *     'icon'   => 'font-icon-user',
 *     'color'  => 'blue',        // optional accent colour class
 *   ]
 *
 * Override getData() in subclasses to return live stats.
 *
 * Usage:
 *   class SiteStatsWidget extends StatsWidget
 *   {
 *       protected string $title = 'Site Statistics';
 *       protected int    $order = 10;
 *
 *       public function getStats(): array
 *       {
 *           return [
 *               ['label' => 'Members',  'value' => Member::get()->count(), 'icon' => 'font-icon-user'],
 *               ['label' => 'Pages',    'value' => Page::get()->count(),   'icon' => 'font-icon-p-4'],
 *           ];
 *       }
 *   }
 */
class StatsWidget extends DashboardWidget
{
    protected string $title = 'Statistics';

    protected int $order = 10;

    protected WidgetWidth $width = WidgetWidth::Full;

    protected string $icon = 'font-icon-chart-line';

    protected int $cacheLifetime = 300;

    /**
     * Returns the list of stat tiles.
     * Override in subclasses.
     *
     * @return array<int, array<string, string|int>>
     */
    public function getStats(): array
    {
        return [
            [
                'label' => 'Total Members',
                'value' => Member::get()->count(),
                'icon'  => 'font-icon-user',
                'color' => 'blue',
            ],
        ];
    }

    public function getData(): array
    {
        $stats = $this->getStats();

        // Normalise defaults
        $normalised = array_map(static function (array $stat): array {
            return array_merge([
                'label'  => '',
                'value'  => 0,
                'delta'  => '',
                'trend'  => 'neutral',
                'icon'   => 'font-icon-chart-bar',
                'color'  => 'default',
            ], $stat);
        }, $stats);

        return [
            'Stats'     => $normalised,
            'StatCount' => count($normalised),
        ];
    }
}
