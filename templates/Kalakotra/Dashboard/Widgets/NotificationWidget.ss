<%-- Dashboard/Widgets/NotificationWidget.ss --%>
<div class="dashboard-widget__inner">
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__icon $Widget.Icon"></span>
        <h3 class="dashboard-widget__title">
            $Widget.Title
            <% if $HasUnread %>
                <span class="notification-widget__badge">$UnreadCount</span>
            <% end_if %>
        </h3>
        <% if $Widget.SupportsRefresh %>
            <button class="dashboard-widget__refresh-btn"
                    type="button"
                    data-refresh-widget="$Widget.Identifier"
                    title="Refresh">
                <span class="font-icon-refresh"></span>
            </button>
        <% end_if %>
    </div>

    <div class="dashboard-widget__body">
        <% if $HasItems %>
            <ul class="notification-widget__list">
                <% loop $Notifications %>
                    <li class="notification-widget__item notification-widget__item--$type<% if $read == false %> notification-widget__item--unread<% end_if %>">
                        <span class="notification-widget__type-indicator"></span>

                        <div class="notification-widget__content">
                            <% if $link %>
                                <a href="$link" class="notification-widget__message">$message</a>
                            <% else %>
                                <span class="notification-widget__message">$message</span>
                            <% end_if %>

                            <% if $timestamp %>
                                <span class="notification-widget__time">$timestamp</span>
                            <% end_if %>
                        </div>
                    </li>
                <% end_loop %>
            </ul>
        <% else %>
            <div class="dashboard-widget__empty-state">
                <span class="font-icon-bell"></span>
                <p>No notifications.</p>
            </div>
        <% end_if %>
    </div>
</div>
