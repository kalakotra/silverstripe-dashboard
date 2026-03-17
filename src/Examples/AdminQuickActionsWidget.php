<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Examples;

use Kalakotra\Dashboard\Widgets\ActionWidget;
use Kalakotra\Dashboard\Widgets\WidgetWidth;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * AdminQuickActionsWidget – Example concrete ActionWidget.
 *
 * Provides one-click shortcuts to common admin tasks.
 *
 * Register via YAML:
 *   Kalakotra\Dashboard\Registry\DashboardRegistry:
 *     widgets:
 *       - Kalakotra\Dashboard\Examples\AdminQuickActionsWidget
 */
class AdminQuickActionsWidget extends ActionWidget
{
    protected string    $title         = 'Quick Actions';
    protected int       $order         = 5;
    protected WidgetWidth $width       = WidgetWidth::Fifth;
    protected bool      $listMode      = true;
    protected int       $cacheLifetime = 0; // Static – no cache needed

    public function canView(Member $member): bool
    {
        return Permission::checkMember($member, 'CMS_ACCESS');
    }

    protected function getActions(): array
    {
        $actions = [];

        // Pages – only if CMS module is installed
        if (Permission::checkMember(\SilverStripe\Security\Security::getCurrentUser(), 'CMS_ACCESS_CMSMain')) {
            $actions[] = [
                'label' => 'New Page',
                'link'  => '/admin/pages/add',
                'icon'  => 'font-icon-plus-circled',
                'style' => 'primary',
            ];
        }

        // Security
        if (Permission::checkMember(\SilverStripe\Security\Security::getCurrentUser(), 'CMS_ACCESS_SecurityAdmin')) {
            $actions[] = [
                'label' => 'New Member',
                'link'  => '/admin/security/users/EditForm/field/users/item/new/edit',
                'icon'  => 'font-icon-user',
                'style' => 'secondary',
            ];
            $actions[] = [
                'label' => 'New Group',
                'link'  => '/admin/security/groups/EditForm/field/groups/item/new/edit',
                'icon'  => 'font-icon-torso',
                'style' => 'secondary',
            ];
        }

        // Assets
        if (Permission::checkMember(\SilverStripe\Security\Security::getCurrentUser(), 'CMS_ACCESS_AssetAdmin')) {
            $actions[] = [
                'label' => 'Upload File',
                'link'  => '/admin/assets/',
                'icon'  => 'font-icon-upload',
                'style' => 'secondary',
            ];
        }

        return $actions;
    }
}
