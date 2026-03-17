<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Tests;

use Kalakotra\Dashboard\Registry\DashboardRegistry;
use Kalakotra\Dashboard\Widgets\DashboardWidget;
use Kalakotra\Dashboard\Widgets\WidgetWidth;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

/**
 * DashboardRegistryTest
 *
 * Run with:
 *   vendor/bin/phpunit app/src/Dashboard/Tests/DashboardRegistryTest.php
 */
class DashboardRegistryTest extends SapphireTest
{
    protected $usesDatabase = true;

    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    private function makeRegistry(array $classes): DashboardRegistry
    {
        $registry = DashboardRegistry::inst();
        $registry->flush();

        // Inject test widget classes via config
        DashboardRegistry::config()->set('widgets', $classes);

        // flush again after config change
        $registry->flush();

        return $registry;
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function testGetWidgetsReturnsInstances(): void
    {
        $registry = $this->makeRegistry([
            StubWidgetA::class,
            StubWidgetB::class,
        ]);

        $widgets = $registry->getWidgets();

        $this->assertCount(2, $widgets);
        $this->assertContainsOnlyInstancesOf(DashboardWidget::class, $widgets);
    }

    public function testWidgetsAreSortedByOrder(): void
    {
        $registry = $this->makeRegistry([
            StubWidgetB::class, // order 20
            StubWidgetA::class, // order 10
        ]);

        $widgets = $registry->getWidgets();
        $orders  = array_map(static fn($w) => $w->getOrder(), $widgets);

        $this->assertSame([10, 20], $orders);
    }

    public function testGetVisibleWidgetsFiltersPermissions(): void
    {
        $registry = $this->makeRegistry([
            StubWidgetAdmin::class,   // canView = ADMIN only
            StubWidgetA::class,       // canView = any CMS user
        ]);

        /** @var Member $basicMember */
        $basicMember = $this->objFromFixture(Member::class, 'basic');

        $visible = $registry->getVisibleWidgets($basicMember);

        $this->assertCount(1, $visible);
        $this->assertInstanceOf(StubWidgetA::class, reset($visible));
    }

    public function testFindByIdentifier(): void
    {
        $registry = $this->makeRegistry([StubWidgetA::class]);

        $widget = $registry->findByIdentifier('StubWidgetA');
        $this->assertInstanceOf(StubWidgetA::class, $widget);

        $missing = $registry->findByIdentifier('NonExistent');
        $this->assertNull($missing);
    }

    public function testInvalidClassIsSkipped(): void
    {
        $registry = $this->makeRegistry([
            'Kalakotra\Dashboard\Tests\NonExistentWidget',
            StubWidgetA::class,
        ]);

        $widgets = $registry->getWidgets();
        $this->assertCount(1, $widgets);
    }

    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    protected static $fixture_file = 'DashboardRegistryTest.yml';
}

/* =========================================================================
   Test stub widgets – defined in-file so no extra files are needed
   ========================================================================= */

class StubWidgetA extends DashboardWidget
{
    protected string    $title = 'Stub A';
    protected int       $order = 10;
    protected WidgetWidth $width = WidgetWidth::Half;

    public function getData(): array { return []; }
}

class StubWidgetB extends DashboardWidget
{
    protected string    $title = 'Stub B';
    protected int       $order = 20;
    protected WidgetWidth $width = WidgetWidth::Half;

    public function getData(): array { return []; }
}

class StubWidgetAdmin extends DashboardWidget
{
    protected string    $title = 'Admin only';
    protected int       $order = 5;
    protected WidgetWidth $width = WidgetWidth::Fifth;

    public function getData(): array { return []; }

    public function canView(Member $member): bool
    {
        return \SilverStripe\Security\Permission::checkMember($member, 'ADMIN');
    }
}
