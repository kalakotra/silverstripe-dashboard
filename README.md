# SilverStripe 6 Dashboard Module

Reusable, extensible CMS Dashboard for SilverStripe 6.  
Widgets are defined in code, registered via YAML config, and rendered inside a responsive CSS Grid layout.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.2 |
| silverstripe/framework | ^6.0 |
| silverstripe/admin | ^3.0 |

---

## Installation

```bash
composer require kalakotra/silverstripe-dashboard
vendor/bin/sake dev/build flush=1
```

---

## Module Structure

```
silverstripe-dashboard/
├── _config/
│   ├── dashboard.yml          # Widget registration & CMS routing
│   ├── dashboard-examples.yml # Example widget registrations
│   └── cache.yml              # PSR-16 cache binding
│
├── client/
│   ├── css/dashboard.css      # CSS Grid layout + all widget styles
│   └── js/dashboard.js        # AJAX refresh, animations, keyboard a11y
│
└── src/
    ├── Controllers/
    │   └── DashboardController.php   # LeftAndMain CMS section
    │
    ├── Registry/
    │   └── DashboardRegistry.php     # Widget loader, sorter, filter
    │
    ├── Services/
    │   └── DashboardCacheService.php # PSR-16 cache wrapper
    │
    ├── Widgets/
    │   ├── DashboardWidget.php       # Abstract base class
    │   ├── WidgetWidth.php           # Width enum (Full/Half/Third/Quarter/Fifth)
    │   ├── TextWidget.php
    │   ├── StatsWidget.php
    │   ├── TableWidget.php
    │   ├── ListWidget.php
    │   ├── ChartWidget.php           # Chart.js integration
    │   ├── ActionWidget.php
    │   ├── NotificationWidget.php
    │   └── ProgressWidget.php
    │
    ├── Examples/
    │   ├── SiteStatsWidget.php
    │   ├── RecentMembersWidget.php
    │   ├── MemberGrowthChartWidget.php
    │   ├── AdminQuickActionsWidget.php
    │   └── SystemHealthWidget.php
    │
    └── Tests/
        └── DashboardRegistryTest.php

templates/
├── Dashboard.ss                      # Main grid layout
└── Widgets/
    ├── TextWidget.ss
    ├── StatsWidget.ss
    ├── TableWidget.ss
    ├── ListWidget.ss
    ├── ChartWidget.ss
    ├── ActionWidget.ss
    ├── NotificationWidget.ss
    └── ProgressWidget.ss
```

---

## Creating a Custom Widget

### 1 — Extend a base widget class

```php
<?php

namespace App\Widgets;

use Kalakotra\Dashboard\Widgets\StatsWidget;
use Kalakotra\Dashboard\Widgets\WidgetWidth;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class BookingStatsWidget extends StatsWidget
{
    protected string    $title         = 'Bookings Overview';
    protected int       $order         = 15;
    protected WidgetWidth $width       = WidgetWidth::Full;
    protected int       $cacheLifetime = 300;

    public function canView(Member $member): bool
    {
        return Permission::checkMember($member, 'CMS_ACCESS_BookingAdmin');
    }

    public function getStats(): array
    {
        return [
            [
                'label' => 'Total Bookings',
                'value' => Booking::get()->count(),
                'icon'  => 'font-icon-calendar',
                'color' => 'blue',
            ],
            [
                'label' => 'This Month',
                'value' => Booking::get()
                    ->filter('Created:GreaterThan', date('Y-m-01'))
                    ->count(),
                'icon'  => 'font-icon-chart-line',
                'color' => 'green',
                'delta' => '+12%',
                'trend' => 'up',
            ],
        ];
    }
}
```

### 2 — Register via YAML

```yaml
Kalakotra\Dashboard\Registry\DashboardRegistry:
  widgets:
    - App\Widgets\BookingStatsWidget
```

### 3 — Run dev/build

```bash
vendor/bin/sake dev/build flush=1
```

---

## Widget Width Reference

| Constant | CSS Class | Grid Span | Use Case |
|---|---|---|---|
| `WidgetWidth::Full` | `dashboard-widget--full` | 5 / 5 | Stats rows, charts |
| `WidgetWidth::Half` | `dashboard-widget--half` | 3 / 5 | Tables, charts |
| `WidgetWidth::Third` | `dashboard-widget--third` | 2 / 5 | Lists, notifications |
| `WidgetWidth::Quarter` | `dashboard-widget--quarter` | 2 / 5 | Progress, compact data |
| `WidgetWidth::Fifth` | `dashboard-widget--fifth` | 1 / 5 | Quick actions |

---

## Widget Types

### `StatsWidget`
KPI tiles with value, label, trend indicator and accent colour.  
Override `getStats(): array`.

### `TableWidget`
Paginated data table with configurable columns and optional CMS deep-links.  
Override `getColumns(): array` and `getRows(): SS_List|array`.

### `ListWidget`
Scrollable item list with icons, subtitles and badges.  
Override `getItems(): SS_List|array`.

### `ChartWidget`
Chart.js 4 chart (bar, line, pie, doughnut, radar).  
Override `getChartData(): array` and optionally `getChartOptions(): array`.

### `ActionWidget`
Quick-action buttons/links in list or grid layout.  
Override `getActions(): array`.

### `NotificationWidget`
Timestamped notification feed with unread count badge.  
Override `getNotifications(): array`. Supports AJAX refresh.

### `ProgressWidget`
Labelled progress bars with semantic colour states.  
Override `getBars(): array`.

### `TextWidget`
Static HTML/text block.  
Override `getContent(): string` or set `$content` property.

---

## Permissions

```php
// Any logged-in CMS user (default)
public function canView(Member $member): bool
{
    return Permission::checkMember($member, 'CMS_ACCESS');
}

// Admin only
public function canView(Member $member): bool
{
    return Permission::checkMember($member, 'ADMIN');
}

// Specific group
public function canView(Member $member): bool
{
    return $member->inGroup('editors');
}
```

---

## Caching

```php
// 5-minute cache (default)
protected int $cacheLifetime = 300;

// Disable caching (static widgets)
protected int $cacheLifetime = 0;

// Custom cache key variance (e.g. per locale)
public function getCacheKey(?Member $member = null): string
{
    return parent::getCacheKey($member) . '_' . i18n::get_locale();
}
```

---

## AJAX Refresh

Widgets opt-in to AJAX refresh by returning `true` from `supportsRefresh()`:

```php
protected bool $supportsRefresh = true;

public function supportsRefresh(): bool
{
    return $this->supportsRefresh;
}
```

The frontend automatically adds a refresh button to the widget header.  
The endpoint is: `GET /admin/dashboard/widgetRefresh/{Identifier}`

You can also trigger programmatic refresh from JavaScript:

```js
// Refresh a single widget
window.SSDashboard.refreshWidget(document.querySelector('[data-widget="MyWidget"]'));

// Refresh all refreshable widgets
window.SSDashboard.refreshAllWidgets();
```

---

## Running Tests

```bash
vendor/bin/phpunit app/src/Dashboard/Tests
```

---

## License

MIT
