<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

/**
 * TextWidget
 *
 * Displays a static block of HTML or plain text.
 * Useful for notices, announcements or documentation links.
 *
 * Usage:
 *   class MyTextWidget extends TextWidget
 *   {
 *       protected string $title   = 'Welcome';
 *       protected string $content = '<p>Welcome to the admin panel.</p>';
 *       protected int    $order   = 5;
 *       protected WidgetWidth $width = WidgetWidth::Full;
 *   }
 */
class TextWidget extends DashboardWidget
{
    protected string $title = 'Text';

    protected int $order = 10;

    protected WidgetWidth $width = WidgetWidth::Full;

    protected string $icon = 'font-icon-edit-list';

    /** The HTML/text content to display. Override in subclasses. */
    protected string $content = '';

    /** Optional CSS class(es) added to the content wrapper. */
    protected string $contentClass = '';

    // Disable caching – text is static
    protected int $cacheLifetime = 0;

    public function getData(): array
    {
        return [
            'Content'      => $this->getContent(),
            'ContentClass' => $this->contentClass,
        ];
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
