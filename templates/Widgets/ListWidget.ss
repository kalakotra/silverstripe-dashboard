<%-- Dashboard/Widgets/ListWidget.ss --%>
<div class="dashboard-widget__inner">
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__icon $Widget.Icon"></span>
        <h3 class="dashboard-widget__title">$Widget.Title</h3>
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
            <ul class="list-widget__list">
                <% loop $Items %>
                    <li class="list-widget__item<% if $read == 'false' %> list-widget__item--unread<% end_if %>">
                        <% if $link %>
                            <a href="$link" class="list-widget__link">
                        <% end_if %>

                        <% if $Up.ShowIcons %>
                            <span class="list-widget__icon $icon"></span>
                        <% end_if %>

                        <span class="list-widget__content">
                            <span class="list-widget__title">$title</span>
                            <% if $subtitle %>
                                <span class="list-widget__subtitle">$subtitle</span>
                            <% end_if %>
                        </span>

                        <% if $badge %>
                            <span class="list-widget__badge $badgeClass">$badge</span>
                        <% end_if %>

                        <% if $link %>
                            </a>
                        <% end_if %>
                    </li>
                <% end_loop %>
            </ul>

            <% if $ViewAllLink %>
                <div class="dashboard-widget__footer">
                    <a href="$ViewAllLink" class="dashboard-widget__view-all">
                        $ViewAllLabel
                        <span class="font-icon-right-open"></span>
                    </a>
                </div>
            <% end_if %>
        <% else %>
            <div class="dashboard-widget__empty-state">
                <span class="font-icon-list"></span>
                <p>No items found.</p>
            </div>
        <% end_if %>
    </div>
</div>
