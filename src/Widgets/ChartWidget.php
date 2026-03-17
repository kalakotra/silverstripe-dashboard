<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

/**
 * ChartWidget
 *
 * Renders a Chart.js chart inside the dashboard.
 * Chart.js is loaded via CDN in the template.
 *
 * Supported chart types: bar, line, pie, doughnut, radar, polarArea
 *
 * Usage:
 *   class BookingsChartWidget extends ChartWidget
 *   {
 *       protected string    $title     = 'Bookings per Month';
 *       protected int       $order     = 40;
 *       protected WidgetWidth $width   = WidgetWidth::Half;
 *       protected string    $chartType = 'bar';
 *
 *       protected function getChartData(): array
 *       {
 *           return [
 *               'labels'   => ['Jan', 'Feb', 'Mar', 'Apr'],
 *               'datasets' => [
 *                   [
 *                       'label'           => 'Bookings',
 *                       'data'            => [12, 34, 28, 45],
 *                       'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
 *                   ],
 *               ],
 *           ];
 *       }
 *   }
 */
class ChartWidget extends DashboardWidget
{
    protected string $title = 'Chart';

    protected int $order = 40;

    protected WidgetWidth $width = WidgetWidth::Half;

    protected string $icon = 'font-icon-chart-bar';

    protected int $cacheLifetime = 300;

    /**
     * Chart.js chart type.
     * One of: bar | line | pie | doughnut | radar | polarArea
     */
    protected string $chartType = 'bar';

    /**
     * Chart height in pixels.
     */
    protected int $chartHeight = 280;

    /**
     * Additional Chart.js options (merged into the options object).
     * Override to customise axes, legends, tooltips, etc.
     *
     * @return array<string, mixed>
     */
    protected function getChartOptions(): array
    {
        return [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'plugins'             => [
                'legend' => ['position' => 'top'],
            ],
        ];
    }

    /**
     * Returns Chart.js-compatible data structure.
     *
     * @return array{labels: string[], datasets: array<int, array<string, mixed>>}
     */
    protected function getChartData(): array
    {
        return [
            'labels'   => [],
            'datasets' => [],
        ];
    }

    public function getData(): array
    {
        $chartData    = $this->getChartData();
        $chartOptions = $this->getChartOptions();

        // Unique canvas ID to avoid Chart.js conflicts when multiple charts exist
        $canvasId = 'dashboard-chart-' . strtolower($this->getIdentifier());

        return [
            'ChartType'    => $this->chartType,
            'ChartHeight'  => $this->chartHeight,
            'CanvasID'     => $canvasId,
            // JSON-encoded for inline <script> injection
            'ChartDataJSON'    => json_encode($chartData),
            'ChartOptionsJSON' => json_encode($chartOptions),
        ];
    }
}
