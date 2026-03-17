<%-- Dashboard/Widgets/StatsWidget.ss --%>
<div class="dashboard-widget__inner">
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__icon $Widget.Icon"></span>
        <h3 class="dashboard-widget__title">$Widget.Title</h3>
    </div>

    <div class="dashboard-widget__body">
        <div class="stats-widget__grid stats-widget__grid--$StatCount">
            <% loop $Stats %>
                <div class="stats-widget__tile stats-widget__tile--$color">

                    <div class="stats-widget__tile-header">
                        <% if $icon %>
                            <span class="stats-widget__tile-icon $icon"></span>
                        <% end_if %>
                        <span class="stats-widget__tile-label">$label</span>
                    </div>

                    <div class="stats-widget__tile-value">$value</div>

                    <% if $delta %>
                        <div class="stats-widget__tile-delta stats-widget__tile-delta--$trend">
                            <% if $trend == 'up' %>▲<% else_if $trend == 'down' %>▼<% else %>—<% end_if %>
                            $delta
                        </div>
                    <% end_if %>

                </div>
            <% end_loop %>
        </div>
    </div>
</div>
