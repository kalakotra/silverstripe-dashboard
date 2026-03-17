<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * AbstractAdminWidget
 *
 * Extended base class for widgets that need flexible, declarative
 * permission control beyond the default "any CMS user" check.
 *
 * FEATURES
 * ─────────
 * 1. Permission code gate        – require one or more SS permission codes
 * 2. Group membership gate       – require member to be in named group(s)
 * 3. Role gate                   – require a specific CMS role
 * 4. ADMIN bypass                – ADMIN always passes (optional, default ON)
 * 5. Custom logic hook           – override authorise() for bespoke checks
 * 6. Multiple-strategy AND/OR    – combine strategies with $permissionMode
 *
 * USAGE EXAMPLES
 * ───────────────
 * // Require single permission code (default behaviour)
 * class MyWidget extends AbstractAdminWidget
 * {
 *     protected array $requiredPermissions = ['CMS_ACCESS_BookingAdmin'];
 * }
 *
 * // Require ALL of several codes (AND mode)
 * class MyWidget extends AbstractAdminWidget
 * {
 *     protected array  $requiredPermissions = ['CMS_ACCESS_ReportAdmin', 'VIEW_DRAFT_CONTENT'];
 *     protected string $permissionMode      = self::MODE_AND;
 * }
 *
 * // Require group membership
 * class MyWidget extends AbstractAdminWidget
 * {
 *     protected array $requiredGroups = ['content-editors', 'site-managers'];
 * }
 *
 * // Mix permissions AND groups (member must satisfy both)
 * class MyWidget extends AbstractAdminWidget
 * {
 *     protected array $requiredPermissions = ['CMS_ACCESS'];
 *     protected array $requiredGroups      = ['premium-editors'];
 * }
 *
 * // Custom logic
 * class MyWidget extends AbstractAdminWidget
 * {
 *     protected function authorise(Member $member): bool
 *     {
 *         return $member->Email === 'ceo@example.com';
 *     }
 * }
 */
abstract class AbstractAdminWidget extends DashboardWidget
{
    // -------------------------------------------------------------------------
    // Permission strategy constants
    // -------------------------------------------------------------------------

    /** Member must hold AT LEAST ONE of the listed permission codes. */
    public const MODE_OR = 'OR';

    /** Member must hold ALL listed permission codes. */
    public const MODE_AND = 'AND';

    // -------------------------------------------------------------------------
    // Permission configuration
    // -------------------------------------------------------------------------

    /**
     * List of SilverStripe permission codes required to view this widget.
     * Leave empty to skip the permission-code check.
     *
     * Example: ['CMS_ACCESS', 'VIEW_DRAFT_CONTENT']
     *
     * @var string[]
     */
    protected array $requiredPermissions = [];

    /**
     * How to combine multiple $requiredPermissions entries.
     * Use MODE_OR (default) or MODE_AND.
     */
    protected string $permissionMode = self::MODE_OR;

    /**
     * List of Group CODE values the member must belong to.
     * Leave empty to skip the group-membership check.
     *
     * Example: ['content-editors', 'site-managers']
     *
     * @var string[]
     */
    protected array $requiredGroups = [];

    /**
     * When true, a member with the ADMIN permission code always passes,
     * regardless of any other checks.
     */
    protected bool $adminBypass = true;

    // -------------------------------------------------------------------------
    // canView – orchestrates all permission strategies
    // -------------------------------------------------------------------------

    /**
     * Final canView() implementation.
     * Runs strategies in order; short-circuits on failure.
     * Override authorise() for custom logic instead of this method.
     *
     * @param Member $member
     * @return bool
     */
    final public function canView(Member $member): bool
    {
        // 1. ADMIN bypass
        if ($this->adminBypass && Permission::checkMember($member, 'ADMIN')) {
            return true;
        }

        // 2. Permission-code check
        if (!empty($this->requiredPermissions)) {
            if (!$this->checkPermissions($member)) {
                return false;
            }
        }

        // 3. Group-membership check
        if (!empty($this->requiredGroups)) {
            if (!$this->checkGroups($member)) {
                return false;
            }
        }

        // 4. Custom hook (override in subclass)
        return $this->authorise($member);
    }

    // -------------------------------------------------------------------------
    // Strategy methods (override for custom logic)
    // -------------------------------------------------------------------------

    /**
     * Custom authorisation hook.
     *
     * Called after the permission-code and group checks pass.
     * Override this in concrete widgets instead of canView().
     *
     * Default: returns true (allow access if all other checks pass).
     *
     * @param Member $member
     * @return bool
     */
    protected function authorise(Member $member): bool
    {
        return true;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Evaluates $requiredPermissions against the given member
     * using either OR or AND mode.
     */
    private function checkPermissions(Member $member): bool
    {
        if ($this->permissionMode === self::MODE_AND) {
            foreach ($this->requiredPermissions as $code) {
                if (!Permission::checkMember($member, $code)) {
                    return false;
                }
            }

            return true;
        }

        // MODE_OR – default
        foreach ($this->requiredPermissions as $code) {
            if (Permission::checkMember($member, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks that the member belongs to at least one of the $requiredGroups.
     * Groups are matched by their Code field.
     */
    private function checkGroups(Member $member): bool
    {
        foreach ($this->requiredGroups as $groupCode) {
            if ($member->inGroup($groupCode)) {
                return true;
            }
        }

        return false;
    }
}
