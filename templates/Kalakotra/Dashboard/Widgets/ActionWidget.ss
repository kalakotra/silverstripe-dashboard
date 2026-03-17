<%-- Dashboard/Widgets/ActionWidget.ss --%>
<div class="dashboard-widget__inner">
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__icon $Widget.Icon"></span>
        <h3 class="dashboard-widget__title">$Widget.Title</h3>
    </div>

    <div class="dashboard-widget__body">
        <% if $HasActions %>
            <% if $ListMode %>
                <ul class="action-widget__list">
                    <% loop $Actions %>
                        <li class="action-widget__item">
                            <a href="$link"
                               class="action-widget__btn action-widget__btn--$style"
                               target="$target">
                                <span class="$icon"></span>
                                $label
                            </a>
                        </li>
                    <% end_loop %>
                </ul>
            <% else %>
                <div class="action-widget__grid">
                    <% loop $Actions %>
                        <a href="$link"
                           class="action-widget__tile action-widget__tile--$style"
                           target="$target">
                            <span class="action-widget__tile-icon $icon"></span>
                            <span class="action-widget__tile-label">$label</span>
                        </a>
                    <% end_loop %>
                </div>
            <% end_if %>
        <% else %>
            <div class="dashboard-widget__empty-state">
                <span class="font-icon-rocket"></span>
                <p>No actions configured.</p>
            </div>
        <% end_if %>
    </div>
</div>
