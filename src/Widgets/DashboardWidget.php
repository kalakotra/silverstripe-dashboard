<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

use Kalakotra\Dashboard\Services\DashboardCacheService;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * DashboardWidget
 *
 * Abstract base class for all Dashboard widgets.
 *
 * EXTENDING:
 *   class MyWidget extends DashboardWidget
 *   {
 *       protected string $title        = 'My Widget';
 *       protected int    $order        = 10;
 *       protected WidgetWidth $width   = WidgetWidth::Half;
 *       protected int    $cacheLifetime = 300;
 *
 *       public function getData(): array
 *       {
 *           return ['value' => 42];
 *       }
 *   }
 *
 * REGISTRATION (YAML):
 *   Kalakotra\Dashboard\Registry\DashboardRegistry:
 *     widgets:
 *       - App\MyModule\Widgets\MyWidget
 */
abstract class DashboardWidget extends ModelData
{
    use Configurable;
    use Injectable;

    // -------------------------------------------------------
    // Widget identity & layout
    // -------------------------------------------------------

    /** Human-readable widget title shown in the CMS. */
    protected string $title = 'Widget';

    /** Sort order within the dashboard grid (ascending). */
    protected int $order = 100;

    /** Column span width. */
    protected WidgetWidth $width = WidgetWidth::Half;

    /**
     * Optional icon name (from SilverStripe icon set or FontAwesome class).
     * Example: 'chart-bar' or 'font-icon-chart-line'
     */
    protected string $icon = 'dashboard';

    // -------------------------------------------------------
    // Caching
    // -------------------------------------------------------

    /**
     * Cache lifetime in seconds.
     * Set to 0 to disable caching for this widget.
     */
    protected int $cacheLifetime = 300;

    // -------------------------------------------------------
    // Public API
    // -------------------------------------------------------

    /**
     * Returns the widget title displayed in the CMS.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns the sort order of this widget.
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Returns the WidgetWidth enum value for this widget.
     */
    public function getWidth(): WidgetWidth
    {
        return $this->width;
    }

    /**
     * Returns the BEM CSS class for the widget's grid column span.
     *
     * Example: 'dashboard-widget--half'
     */
    public function getWidthClass(): string
    {
        return $this->width->toCssClass();
    }

    /**
     * Returns the short identifier for this widget (class basename).
     * Used in AJAX refresh endpoint URLs.
     *
     * Example: 'StatsWidget'
     */
    public function getIdentifier(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Returns the icon identifier for this widget.
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Returns the preferred SilverStripe template name for this widget.
     *
     * Override to provide a custom primary template path.
     */
    public function getTemplate(): string
    {
        return 'Kalakotra/Dashboard/Widgets/' . $this->getIdentifier();
    }

    /**
     * Returns ordered template candidates for renderWith().
     *
     * First tries the concrete widget class template, then walks up
     * widget inheritance chain (e.g. AdminQuickActionsWidget -> ActionWidget).
     * This prevents hard failures when a concrete example widget does not
     * provide its own .ss file but its parent does.
     *
     * @return string[]
     */
    protected function getTemplateCandidates(): array
    {
        $templates = [];
        $class = new \ReflectionClass($this);

        while ($class && $class->getName() !== self::class) {
            $templates[] = 'Kalakotra/Dashboard/Widgets/' . $class->getShortName();
            $class = $class->getParentClass();
        }

        // Keep order while removing duplicates in case of custom overrides.
        return array_values(array_unique($templates));
    }

    /**
     * Returns the data array passed to the widget template.
     * Override in concrete widgets to return live data.
     *
     * @return array<string, mixed>
     */
    abstract public function getData(): array;

    /**
     * Renders the widget using its template.
     * Merges getData() into the template scope.
     *
     * @return DBHTMLText
     */
    public function render(): DBHTMLText
    {
        $data = $this->getCachedData();

        return $this->customise(array_merge([
            'Widget' => $this,
        ], $data))->renderWith($this->getTemplateCandidates());
    }

    // -------------------------------------------------------
    // Permissions
    // -------------------------------------------------------

    /**
     * Determines whether the given member can view this widget.
     *
     * Default: any logged-in CMS user.
     * Override to restrict by group, role or custom logic.
     *
     * @param Member $member
     * @return bool
     */
    public function canView(Member $member): bool
    {
        return Permission::checkMember($member, 'CMS_ACCESS');
    }

    // -------------------------------------------------------
    // Caching
    // -------------------------------------------------------

    /**
     * Returns the cache lifetime in seconds for this widget.
     * Return 0 to disable caching.
     */
    public function getCacheLifetime(): int
    {
        return $this->cacheLifetime;
    }

    /**
     * Returns a cache key unique to this widget and member.
     * Override to include additional variance (e.g., locale, date).
     *
     * @param Member|null $member
     * @return string
     */
    public function getCacheKey(?Member $member = null): string
    {
        $memberPart = $member ? $member->ID : 'guest';

        return implode('_', [
            'dashboard_widget',
            strtolower($this->getIdentifier()),
            $memberPart,
        ]);
    }

    /**
     * Returns data from cache if available, otherwise calls getData()
     * and stores the result.
     *
     * @return array<string, mixed>
     */
    protected function getCachedData(): array
    {
        if ($this->getCacheLifetime() === 0) {
            return $this->getData();
        }

        /** @var DashboardCacheService $cache */
        $cache = DashboardCacheService::singleton();
        $key   = $this->getCacheKey();

        $cached = $cache->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $data = $this->getData();
        $cache->set($key, $data, $this->getCacheLifetime());

        return $data;
    }

    /**
     * Invalidates the cache for this widget.
     */
    public function invalidateCache(): void
    {
        DashboardCacheService::singleton()->delete($this->getCacheKey());
    }

    // -------------------------------------------------------
    // Template helpers
    // -------------------------------------------------------

    /**
     * Returns the full CSS class string for the widget wrapper element.
     *
     * Example: 'dashboard-widget dashboard-widget--half'
     */
    public function getWrapperClasses(): string
    {
        return implode(' ', [
            'dashboard-widget',
            $this->getWidthClass(),
        ]);
    }

    /**
     * Returns true if this widget supports AJAX refresh.
     * Override and return true in widgets that support live refresh.
     */
    public function supportsRefresh(): bool
    {
        return false;
    }
}
