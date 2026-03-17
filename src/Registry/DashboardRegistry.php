<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Registry;

use Kalakotra\Dashboard\Widgets\DashboardWidget;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Security\Member;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;

/**
 * DashboardRegistry
 *
 * Central registry for all dashboard widgets.
 * Widgets are registered via YAML config:
 *
 *   Kalakotra\Dashboard\Registry\DashboardRegistry:
 *     widgets:
 *       - Kalakotra\Dashboard\Widgets\StatsWidget
 *
 * The registry instantiates, sorts and filters widgets
 * based on the current Member's permissions.
 */
class DashboardRegistry
{
    use Configurable;
    use Injectable;

    /**
     * List of widget class names registered via YAML.
     *
     * @config
     * @var string[]
     */
    private static array $widgets = [];

    /**
     * Instantiated widget objects (lazy-loaded).
     *
     * @var DashboardWidget[]|null
     */
    private ?array $resolved = null;

    /**
     * Returns all registered widget instances, sorted by order.
     *
     * @return DashboardWidget[]
     */
    public function getWidgets(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $classes = $this->config()->get('widgets') ?? [];
        $widgets = [];

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                $this->log("Dashboard: widget class '{$class}' not found, skipping.");
                continue;
            }

            $instance = Injector::inst()->create($class);

            if (!($instance instanceof DashboardWidget)) {
                $this->log("Dashboard: '{$class}' does not extend DashboardWidget, skipping.");
                continue;
            }

            $widgets[] = $instance;
        }

        // Sort by order ascending
        usort($widgets, static fn(DashboardWidget $a, DashboardWidget $b) => $a->getOrder() <=> $b->getOrder());

        $this->resolved = $widgets;

        return $this->resolved;
    }

    /**
     * Returns widgets visible to the given Member,
     * filtered by canView() permission check.
     *
     * @param Member $member
     * @return DashboardWidget[]
     */
    public function getVisibleWidgets(Member $member): array
    {
        return array_filter(
            $this->getWidgets(),
            static fn(DashboardWidget $widget) => $widget->canView($member)
        );
    }

    /**
     * Returns visible widgets as an SilverStripe ArrayList
     * (useful for template rendering).
     *
     * @param Member $member
     * @return ArrayList
     */
    public function getWidgetList(Member $member): ArrayList
    {
        return ArrayList::create(array_values($this->getVisibleWidgets($member)));
    }

    /**
     * Finds a single widget by its short identifier (class basename).
     *
     * @param string $identifier  e.g. "StatsWidget"
     * @return DashboardWidget|null
     */
    public function findByIdentifier(string $identifier): ?DashboardWidget
    {
        foreach ($this->getWidgets() as $widget) {
            if ($widget->getIdentifier() === $identifier) {
                return $widget;
            }
        }

        return null;
    }

    /**
     * Clears the resolved widget cache (useful in tests).
     */
    public function flush(): void
    {
        $this->resolved = null;
    }

    // -------------------------------------------------------
    // Internals
    // -------------------------------------------------------

    private function log(string $message): void
    {
        /** @var LoggerInterface $logger */
        $logger = Injector::inst()->get(LoggerInterface::class);
        $logger->warning($message);
    }
}
