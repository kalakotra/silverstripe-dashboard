<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

/**
 * ActionWidget
 *
 * Displays a list of quick-action buttons/links for common admin tasks.
 *
 * Action definition format:
 *   [
 *     'label'  => 'Create new User',
 *     'link'   => '/admin/security/users/add',
 *     'icon'   => 'font-icon-plus-circled',
 *     'style'  => 'primary',    // 'primary' | 'secondary' | 'danger'
 *     'target' => '_self',      // optional; '_blank' for new tab
 *   ]
 *
 * Usage:
 *   class AdminActionsWidget extends ActionWidget
 *   {
 *       protected string $title = 'Quick Actions';
 *       protected int    $order = 5;
 *
 *       protected function getActions(): array
 *       {
 *           return [
 *               ['label' => 'New Page',   'link' => '/admin/pages/add',     'icon' => 'font-icon-plus'],
 *               ['label' => 'New Member', 'link' => '/admin/security/add',  'icon' => 'font-icon-user'],
 *           ];
 *       }
 *   }
 */
class ActionWidget extends DashboardWidget
{
    protected string $title = 'Quick Actions';

    protected int $order = 5;

    protected WidgetWidth $width = WidgetWidth::Fifth;

    protected string $icon = 'font-icon-rocket';

    // Actions are static – no need to cache
    protected int $cacheLifetime = 0;

    /** Display actions as a vertical list (false = button grid). */
    protected bool $listMode = true;

    /**
     * Returns the list of action definitions.
     * Override in subclasses.
     *
     * @return array<int, array<string, string>>
     */
    protected function getActions(): array
    {
        return [];
    }

    public function getData(): array
    {
        $actions = $this->getActions();

        $normalised = array_map(static function (array $action): array {
            return array_merge([
                'label'  => 'Action',
                'link'   => '#',
                'icon'   => 'font-icon-right-open',
                'style'  => 'secondary',
                'target' => '_self',
            ], $action);
        }, $actions);

        return [
            'Actions'    => $normalised,
            'HasActions' => count($normalised) > 0,
            'ListMode'   => $this->listMode,
        ];
    }
}
