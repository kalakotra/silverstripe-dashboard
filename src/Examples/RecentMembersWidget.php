<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Examples;

use Kalakotra\Dashboard\Widgets\TableWidget;
use Kalakotra\Dashboard\Widgets\WidgetWidth;
use SilverStripe\Model\List\SS_List;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * RecentMembersWidget – Example concrete TableWidget implementation.
 *
 * Displays the 10 most recently registered members.
 *
 * Register via YAML:
 *   Kalakotra\Dashboard\Registry\DashboardRegistry:
 *     widgets:
 *       - Kalakotra\Dashboard\Examples\RecentMembersWidget
 */
class RecentMembersWidget extends TableWidget
{
    protected string    $title         = 'Recent Members';
    protected int       $order         = 20;
    protected WidgetWidth $width       = WidgetWidth::Half;
    protected int       $limit         = 10;
    protected string    $viewAllLink   = '/admin/security/users';
    protected string    $viewAllLabel  = 'Manage members';
    protected int       $cacheLifetime = 120; // 2 minutes

    public function canView(Member $member): bool
    {
        return Permission::checkMember($member, 'CMS_ACCESS_SecurityAdmin');
    }

    protected function getColumns(): array
    {
        return [
            ['field' => 'FirstName',  'label' => 'First Name'],
            ['field' => 'Surname',    'label' => 'Surname'],
            ['field' => 'Email',      'label' => 'Email'],
            ['field' => 'Created',    'label' => 'Registered'],
        ];
    }

    protected function getRows(): SS_List|array
    {
        return Member::get()
            ->sort('Created', 'DESC')
            ->limit($this->limit);
    }
}
