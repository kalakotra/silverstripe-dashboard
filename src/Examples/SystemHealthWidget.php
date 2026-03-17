<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Examples;

use Kalakotra\Dashboard\Widgets\ProgressWidget;
use Kalakotra\Dashboard\Widgets\WidgetWidth;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * SystemHealthWidget – Example concrete ProgressWidget.
 *
 * Displays PHP memory, disk usage and DB table count as progress bars.
 *
 * Register via YAML:
 *   Kalakotra\Dashboard\Registry\DashboardRegistry:
 *     widgets:
 *       - Kalakotra\Dashboard\Examples\SystemHealthWidget
 */
class SystemHealthWidget extends ProgressWidget
{
    protected string    $title         = 'System Health';
    protected int       $order         = 60;
    protected WidgetWidth $width       = WidgetWidth::Quarter;
    protected int       $cacheLifetime = 60; // 1 minute – relatively fast-changing

    public function canView(Member $member): bool
    {
        // Admin only – contains server-level information
        return Permission::checkMember($member, 'ADMIN');
    }

    protected function getBars(): array
    {
        return [
            $this->memoryBar(),
            $this->diskBar(),
        ];
    }

    // -------------------------------------------------------------------------
    // Individual bar builders
    // -------------------------------------------------------------------------

    private function memoryBar(): array
    {
        $limitStr = ini_get('memory_limit');
        $limitBytes = $this->parseBytes($limitStr);
        $usedBytes  = memory_get_peak_usage(true);

        if ($limitBytes <= 0) {
            // memory_limit = -1 means unlimited
            return [
                'label'   => 'PHP Memory',
                'current' => round($usedBytes / 1024 / 1024, 1),
                'max'     => 0,
                'unit'    => ' MB',
                'color'   => 'blue',
                'note'    => 'Limit: unlimited',
            ];
        }

        $usedMb  = round($usedBytes / 1024 / 1024, 1);
        $limitMb = round($limitBytes / 1024 / 1024, 1);

        return [
            'label'   => 'PHP Memory',
            'current' => $usedMb,
            'max'     => $limitMb,
            'unit'    => ' MB',
            'color'   => 'blue',
            'note'    => "Peak usage: {$usedMb} MB of {$limitMb} MB",
        ];
    }

    private function diskBar(): array
    {
        $path = defined('BASE_PATH') ? BASE_PATH : '/';

        $free  = (float) @disk_free_space($path);
        $total = (float) @disk_total_space($path);

        if ($total <= 0) {
            return ['label' => 'Disk Space', 'current' => 0, 'max' => 100, 'unit' => '%', 'color' => 'green'];
        }

        $usedGb  = round(($total - $free) / 1073741824, 1);
        $totalGb = round($total / 1073741824, 1);

        return [
            'label'   => 'Disk Space',
            'current' => $usedGb,
            'max'     => $totalGb,
            'unit'    => ' GB',
            'color'   => 'green',
            'note'    => "Used: {$usedGb} GB of {$totalGb} GB",
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Converts PHP ini shorthand memory values to bytes.
     * e.g. "128M" → 134217728
     */
    private function parseBytes(string $val): int
    {
        $val  = trim($val);
        $last = strtolower($val[-1] ?? '');
        $num  = (int) $val;

        return match ($last) {
            'g' => $num * 1073741824,
            'm' => $num * 1048576,
            'k' => $num * 1024,
            default => $num,
        };
    }
}
