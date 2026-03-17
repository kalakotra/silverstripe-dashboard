<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Examples;

use Kalakotra\Dashboard\Widgets\StatsWidget;
use Kalakotra\Dashboard\Widgets\WidgetWidth;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\CMS\Model\SiteTree;

/**
 * SiteStatsWidget – Example concrete StatsWidget implementation.
 *
 * Displays site-wide KPIs: total members, CMS pages, and admins.
 *
 * Register via YAML:
 *   Kalakotra\Dashboard\Registry\DashboardRegistry:
 *     widgets:
 *       - Kalakotra\Dashboard\Examples\SiteStatsWidget
 */
class SiteStatsWidget extends StatsWidget
{
    protected string    $title         = 'Site Overview';
    protected int       $order         = 10;
    protected WidgetWidth $width       = WidgetWidth::Full;
    protected int       $cacheLifetime = 300; // 5 minutes

    public function canView(Member $member): bool
    {
        // Only CMS admins and members with ADMIN permission
        return Permission::checkMember($member, 'ADMIN')
            || Permission::checkMember($member, 'CMS_ACCESS_LeftAndMain');
    }

    public function getStats(): array
    {
        $totalMembers = Member::get()->count();
        $adminCount   = Permission::get_members_by_permission('ADMIN')->count();

        // SiteTree may not be installed — guard gracefully
        $pageCount = class_exists(SiteTree::class)
            ? SiteTree::get()->count()
            : 0;

        return [
            [
                'label' => 'Total Members',
                'value' => number_format($totalMembers),
                'icon'  => 'font-icon-user',
                'color' => 'blue',
                'delta' => '',
                'trend' => 'neutral',
            ],
            [
                'label' => 'CMS Pages',
                'value' => number_format($pageCount),
                'icon'  => 'font-icon-p-4',
                'color' => 'green',
                'delta' => '',
                'trend' => 'neutral',
            ],
            [
                'label' => 'Administrators',
                'value' => number_format($adminCount),
                'icon'  => 'font-icon-torso',
                'color' => 'purple',
                'delta' => '',
                'trend' => 'neutral',
            ],
        ];
    }
}
