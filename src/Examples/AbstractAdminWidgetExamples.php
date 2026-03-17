<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Examples;

use Kalakotra\Dashboard\Widgets\AbstractAdminWidget;
use Kalakotra\Dashboard\Widgets\WidgetWidth;
use Kalakotra\Dashboard\Widgets\StatsWidget;
use SilverStripe\Security\Member;

/**
 * AbstractAdminWidget usage examples.
 *
 * These are illustrative classes showing all three permission strategies
 * available via AbstractAdminWidget.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Example 1: Permission-code gate (OR mode – default)
// Visible to any member holding CMS_ACCESS_ReportAdmin OR ADMIN
// ─────────────────────────────────────────────────────────────────────────────
class ReportAdminWidget extends AbstractAdminWidget
{
    protected string $title  = 'Report Statistics';
    protected int    $order  = 70;
    protected WidgetWidth $width = WidgetWidth::Half;

    // Only these codes grant access (OR logic by default)
    protected array $requiredPermissions = ['CMS_ACCESS_ReportAdmin'];

    public function getData(): array
    {
        return ['stats' => []];
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Example 2: Permission-code gate (AND mode)
// Member must hold BOTH codes to see this widget
// ─────────────────────────────────────────────────────────────────────────────
class SensitiveFinanceWidget extends AbstractAdminWidget
{
    protected string $title  = 'Finance Summary';
    protected int    $order  = 80;
    protected WidgetWidth $width = WidgetWidth::Half;

    protected array  $requiredPermissions = [
        'CMS_ACCESS_FinanceAdmin',
        'VIEW_DRAFT_CONTENT',         // must hold both
    ];

    protected string $permissionMode = self::MODE_AND;

    public function getData(): array
    {
        return ['revenue' => 0];
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Example 3: Group membership gate
// Member must belong to "senior-editors" OR "site-managers" group
// ─────────────────────────────────────────────────────────────────────────────
class EditorialWidget extends AbstractAdminWidget
{
    protected string $title  = 'Editorial Queue';
    protected int    $order  = 90;
    protected WidgetWidth $width = WidgetWidth::Third;

    // Only members of these groups (by Code) see this widget
    protected array $requiredGroups = ['senior-editors', 'site-managers'];

    public function getData(): array
    {
        return ['queue' => []];
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Example 4: Combined – permission code + group membership + custom logic
// Member must satisfy ALL three layers
// ─────────────────────────────────────────────────────────────────────────────
class VIPWidget extends AbstractAdminWidget
{
    protected string $title  = 'VIP Dashboard';
    protected int    $order  = 95;
    protected WidgetWidth $width = WidgetWidth::Full;

    // Layer 1: must have CMS_ACCESS
    protected array $requiredPermissions = ['CMS_ACCESS'];

    // Layer 2: must be in "vip-managers" group
    protected array $requiredGroups = ['vip-managers'];

    // Layer 3: custom – only verified accounts (e.g. custom field)
    protected function authorise(Member $member): bool
    {
        return (bool) $member->getField('IsVerified');
    }

    public function getData(): array
    {
        return ['vip' => true];
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Example 5: Disable admin bypass
// Even ADMIN users must be in the "auditors" group
// ─────────────────────────────────────────────────────────────────────────────
class AuditWidget extends AbstractAdminWidget
{
    protected string $title       = 'Audit Log';
    protected int    $order       = 99;
    protected WidgetWidth $width  = WidgetWidth::Half;

    // Disable the ADMIN bypass – all rules apply to everyone
    protected bool   $adminBypass = false;

    protected array  $requiredGroups = ['auditors'];

    public function getData(): array
    {
        return ['log' => []];
    }
}
