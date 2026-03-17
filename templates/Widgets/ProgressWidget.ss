<%-- Dashboard/Widgets/ProgressWidget.ss --%>
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
        <% if $HasBars %>
            <ul class="progress-widget__list">
                <% loop $Bars %>
                    <li class="progress-widget__item">
                        <div class="progress-widget__meta">
                            <span class="progress-widget__label">$label</span>
                            <span class="progress-widget__value">$current$unit</span>
                        </div>

                        <div class="progress-widget__track">
                            <div class="progress-widget__fill
                                        progress-widget__fill--$color
                                        <% if $isDanger %>progress-widget__fill--danger<% else_if $isWarning %>progress-widget__fill--warning<% end_if %>"
                                 style="width: $percentInt%;"
                                 role="progressbar"
                                 aria-valuenow="$percentInt"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                            </div>
                        </div>

                        <% if $note %>
                            <p class="progress-widget__note">$note</p>
                        <% end_if %>
                    </li>
                <% end_loop %>
            </ul>
        <% else %>
            <div class="dashboard-widget__empty-state">
                <span class="font-icon-chart-bar"></span>
                <p>No progress data available.</p>
            </div>
        <% end_if %>
    </div>
</div>
