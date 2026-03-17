<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

/**
 * NotificationWidget
 *
 * Displays a scrollable list of system or application notifications.
 *
 * Notification definition format:
 *   [
 *     'message'   => 'New user registered.',
 *     'type'      => 'info',        // 'info' | 'success' | 'warning' | 'error'
 *     'timestamp' => '2 hours ago', // human-readable relative time
 *     'link'      => '/admin/...',  // optional deep-link
 *     'read'      => false,         // marks as unread (bold)
 *   ]
 *
 * Usage:
 *   class AppNotificationsWidget extends NotificationWidget
 *   {
 *       protected string $title = 'Notifications';
 *
 *       protected function getNotifications(): array
 *       {
 *           return SystemNotification::get()
 *               ->sort('Created DESC')
 *               ->limit(10)
 *               ->map(...);
 *       }
 *   }
 */
class NotificationWidget extends DashboardWidget
{
    protected string $title = 'Notifications';

    protected int $order = 15;

    protected WidgetWidth $width = WidgetWidth::Third;

    protected string $icon = 'font-icon-bell';

    protected int $cacheLifetime = 60;

    protected int $limit = 10;

    protected bool $supportsRefresh = true;

    /**
     * Returns notification items.
     * Override in subclasses.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getNotifications(): array
    {
        return [];
    }

    public function supportsRefresh(): bool
    {
        return $this->supportsRefresh;
    }

    public function getData(): array
    {
        $notifications = array_slice($this->getNotifications(), 0, $this->limit);

        $normalised = array_map(static function (array $notification): array {
            return array_merge([
                'message'   => '',
                'type'      => 'info',
                'timestamp' => '',
                'link'      => '',
                'read'      => true,
            ], $notification);
        }, $notifications);

        $unreadCount = count(array_filter($normalised, static fn($n) => !$n['read']));

        return [
            'Notifications' => $normalised,
            'Count'         => count($normalised),
            'HasItems'      => count($normalised) > 0,
            'UnreadCount'   => $unreadCount,
            'HasUnread'     => $unreadCount > 0,
        ];
    }
}
