<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

/**
 * ProgressWidget
 *
 * Displays one or more labeled progress bars with percentage fill.
 *
 * Bar definition format:
 *   [
 *     'label'   => 'Storage used',
 *     'current' => 74,        // current value (numeric)
 *     'max'     => 100,       // max value (numeric, default 100)
 *     'unit'    => '%',       // display unit appended to value label
 *     'color'   => 'blue',    // 'blue' | 'green' | 'yellow' | 'red' | 'purple'
 *     'note'    => '74 of 100 GB used',  // optional descriptive note
 *   ]
 *
 * Usage:
 *   class DiskUsageWidget extends ProgressWidget
 *   {
 *       protected string $title = 'Resource Usage';
 *       protected int    $order = 50;
 *
 *       protected function getBars(): array
 *       {
 *           return [
 *               ['label' => 'CPU',     'current' => 34, 'max' => 100, 'unit' => '%', 'color' => 'blue'],
 *               ['label' => 'Memory',  'current' => 61, 'max' => 100, 'unit' => '%', 'color' => 'yellow'],
 *               ['label' => 'Storage', 'current' => 88, 'max' => 100, 'unit' => '%', 'color' => 'red'],
 *           ];
 *       }
 *   }
 */
class ProgressWidget extends DashboardWidget
{
    protected string $title = 'Progress';

    protected int $order = 50;

    protected WidgetWidth $width = WidgetWidth::Quarter;

    protected string $icon = 'font-icon-chart-bar';

    protected int $cacheLifetime = 120;

    /**
     * Returns the list of progress bars.
     * Override in subclasses.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getBars(): array
    {
        return [];
    }

    public function getData(): array
    {
        $bars = $this->getBars();

        $normalised = array_map(static function (array $bar): array {
            $current  = (float) ($bar['current'] ?? 0);
            $max      = (float) ($bar['max'] ?? 100);
            $percent  = $max > 0 ? round(($current / $max) * 100, 1) : 0;

            return array_merge([
                'label'   => '',
                'current' => $current,
                'max'     => $max,
                'unit'    => '%',
                'color'   => 'blue',
                'note'    => '',
            ], $bar, [
                'percent'     => $percent,
                'percentInt'  => (int) $percent,
                'isWarning'   => $percent >= 75,
                'isDanger'    => $percent >= 90,
            ]);
        }, $bars);

        return [
            'Bars'     => $normalised,
            'BarCount' => count($normalised),
            'HasBars'  => count($normalised) > 0,
        ];
    }
}
