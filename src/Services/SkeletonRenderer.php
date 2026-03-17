<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Services;

use SilverStripe\Core\Injector\Injectable;

/**
 * SkeletonRenderer
 *
 * Generates skeleton-loader HTML fragments for each widget type.
 * The JavaScript dashboard.js inserts these into widget bodies
 * before an AJAX refresh completes, giving an instant loading feel.
 *
 * USAGE IN JS (dashboard.js does this automatically):
 *   The skeleton is pre-rendered server-side and embedded as a
 *   data-skeleton attribute on each .dashboard-widget element.
 *   JS swaps it in on refresh start, then replaces with real content.
 *
 * USAGE IN PHP (optional – for server-side skeleton injection):
 *   $html = SkeletonRenderer::inst()->forType('stats', 3);
 */
class SkeletonRenderer
{
    use Injectable;

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Returns skeleton HTML for a given widget type.
     *
     * @param string $type    One of: stats | table | list | progress | chart | text | action | notification
     * @param int    $count   Number of placeholder rows/items/tiles to render
     * @return string         Raw HTML (safe to embed via {$SkeletonHTML.RAW})
     */
    public function forType(string $type, int $count = 4): string
    {
        return match (strtolower($type)) {
            'stats'        => $this->statsskeleton($count),
            'table'        => $this->tableSkeleton($count),
            'list'         => $this->listSkeleton($count),
            'progress'     => $this->progressSkeleton($count),
            'chart'        => $this->chartSkeleton(),
            'notification' => $this->notificationSkeleton($count),
            'action'       => $this->actionSkeleton($count),
            default        => $this->textSkeleton(),
        };
    }

    // -------------------------------------------------------------------------
    // Skeleton builders
    // -------------------------------------------------------------------------

    public function statsSkeleton(int $count = 4): string
    {
        $tiles = '';

        for ($i = 0; $i < $count; $i++) {
            $tiles .= '
            <div class="skeleton-stat-tile">
                <div class="skeleton skeleton-line skeleton-line--50"></div>
                <div class="skeleton skeleton-line skeleton-line--xl skeleton-line--75"></div>
                <div class="skeleton skeleton-line skeleton-line--sm skeleton-line--33"></div>
            </div>';
        }

        return '<div class="skeleton-stat-grid">' . $tiles . '</div>';
    }

    public function tableSkeleton(int $rows = 5): string
    {
        $headerCells = str_repeat(
            '<div class="skeleton skeleton-line skeleton-line--full" style="flex:1;"></div>',
            3
        );

        $rowCells = str_repeat(
            '<div class="skeleton skeleton-line skeleton-line--full" style="flex:1;"></div>',
            3
        );

        $tableRows = '';

        for ($i = 0; $i < $rows; $i++) {
            $tableRows .= '<div class="skeleton-table-row">' . $rowCells . '</div>';
        }

        return '
        <div class="skeleton-table">
            <div class="skeleton-table-header">' . $headerCells . '</div>
            ' . $tableRows . '
        </div>';
    }

    public function listSkeleton(int $items = 6): string
    {
        $listItems = '';

        for ($i = 0; $i < $items; $i++) {
            $width = ['full', '75', '50'][$i % 3];
            $listItems .= '
            <div class="skeleton-list-item">
                <div class="skeleton skeleton-circle" style="width:30px;height:30px;flex-shrink:0;"></div>
                <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                    <div class="skeleton skeleton-line skeleton-line--' . $width . '"></div>
                    <div class="skeleton skeleton-line skeleton-line--sm skeleton-line--50"></div>
                </div>
            </div>';
        }

        return '<div class="skeleton-list">' . $listItems . '</div>';
    }

    public function progressSkeleton(int $bars = 3): string
    {
        $items = '';

        for ($i = 0; $i < $bars; $i++) {
            $width = ['75', '50', '33'][$i % 3];
            $items .= '
            <div class="skeleton-progress-item">
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <div class="skeleton skeleton-line skeleton-line--' . $width . '" style="margin:0;"></div>
                    <div class="skeleton skeleton-line skeleton-line--33" style="margin:0;"></div>
                </div>
                <div class="skeleton skeleton-progress-track"></div>
            </div>';
        }

        return '<div class="skeleton-progress-list">' . $items . '</div>';
    }

    public function chartSkeleton(int $height = 240): string
    {
        return '
        <div style="display:flex;align-items:flex-end;gap:10px;height:' . $height . 'px;padding-bottom:4px;">
            ' . $this->chartBars() . '
        </div>
        <div class="skeleton skeleton-line skeleton-line--50" style="margin-top:10px;"></div>';
    }

    public function notificationSkeleton(int $items = 5): string
    {
        $listItems = '';

        for ($i = 0; $i < $items; $i++) {
            $titleWidth = ['full', '75', '50'][$i % 3];
            $listItems .= '
            <div class="skeleton-list-item">
                <div class="skeleton" style="width:4px;height:36px;border-radius:4px;flex-shrink:0;"></div>
                <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                    <div class="skeleton skeleton-line skeleton-line--' . $titleWidth . '"></div>
                    <div class="skeleton skeleton-line skeleton-line--sm skeleton-line--33"></div>
                </div>
            </div>';
        }

        return '<div class="skeleton-list">' . $listItems . '</div>';
    }

    public function actionSkeleton(int $items = 4): string
    {
        $listItems = '';

        for ($i = 0; $i < $items; $i++) {
            $listItems .= '
            <div class="skeleton skeleton-block" style="height:40px;margin-bottom:8px;"></div>';
        }

        return '<div>' . $listItems . '</div>';
    }

    public function textSkeleton(): string
    {
        return '
        <div>
            <div class="skeleton skeleton-line skeleton-line--full"></div>
            <div class="skeleton skeleton-line skeleton-line--75"></div>
            <div class="skeleton skeleton-line skeleton-line--full"></div>
            <div class="skeleton skeleton-line skeleton-line--50"></div>
        </div>';
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function chartBars(): string
    {
        $heights  = [40, 70, 55, 85, 60, 90, 45, 75];
        $bars     = '';

        foreach ($heights as $pct) {
            $bars .= '<div class="skeleton" style="flex:1;height:' . $pct . '%;border-radius:4px 4px 0 0;"></div>';
        }

        return $bars;
    }
}
