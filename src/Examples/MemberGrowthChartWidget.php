<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Examples;

use Kalakotra\Dashboard\Widgets\ChartWidget;
use Kalakotra\Dashboard\Widgets\WidgetWidth;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * MemberGrowthChartWidget – Example concrete ChartWidget implementation.
 *
 * Renders a bar chart showing member registrations per month
 * for the past 12 months. Uses direct DB query for performance.
 *
 * Register via YAML:
 *   Kalakotra\Dashboard\Registry\DashboardRegistry:
 *     widgets:
 *       - Kalakotra\Dashboard\Examples\MemberGrowthChartWidget
 */
class MemberGrowthChartWidget extends ChartWidget
{
    protected string    $title         = 'Member Growth (12 months)';
    protected int       $order         = 30;
    protected WidgetWidth $width       = WidgetWidth::Half;
    protected string    $chartType     = 'bar';
    protected int       $chartHeight   = 260;
    protected int       $cacheLifetime = 600; // 10 minutes

    public function canView(Member $member): bool
    {
        return Permission::checkMember($member, 'CMS_ACCESS_SecurityAdmin');
    }

    protected function getChartData(): array
    {
        $rows = $this->queryMonthlyRegistrations();

        $labels = [];
        $data   = [];

        foreach ($rows as $row) {
            $labels[] = $row['month'];
            $data[]   = (int) $row['total'];
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'New Members',
                    'data'            => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.75)',
                    'borderColor'     => 'rgba(59, 130, 246, 1)',
                    'borderWidth'     => 1,
                    'borderRadius'    => 4,
                ],
            ],
        ];
    }

    protected function getChartOptions(): array
    {
        return [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'plugins'             => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'mode'      => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => ['precision' => 0],
                    'grid'        => ['color' => 'rgba(0,0,0,.06)'],
                ],
                'x' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }

    /**
     * Queries the Member table for monthly registration counts
     * over the past 12 months.
     *
     * @return array<int, array{month: string, total: string}>
     */
    private function queryMonthlyRegistrations(): array
    {
        $sql = "
            SELECT
                DATE_FORMAT(\"Created\", '%b %Y') AS month,
                DATE_FORMAT(\"Created\", '%Y-%m')  AS sort_key,
                COUNT(*)                            AS total
            FROM \"Member\"
            WHERE \"Created\" >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month, sort_key
            ORDER BY sort_key ASC
        ";

        $results = DB::query($sql);
        $rows    = [];

        foreach ($results as $row) {
            $rows[] = $row;
        }

        return $rows;
    }
}
