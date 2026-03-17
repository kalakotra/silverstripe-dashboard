<%-- Dashboard/Dashboard.ss --%>
<%-- Main CMS dashboard layout rendered by DashboardController --%>
<div class="dashboard" id="dashboard-root">

    <div class="dashboard__header">
        <h1 class="dashboard__title">
            <span class="font-icon-dashboard"></span>
            Dashboard
        </h1>
        <div class="dashboard__actions">
            <button class="dashboard__refresh-all btn btn-outline-secondary btn-sm"
                    type="button"
                    title="Refresh all widgets">
                <span class="font-icon-refresh"></span>
                Refresh
            </button>
        </div>
    </div>

    <% if $Widgets %>
        <div class="dashboard__grid" id="dashboard-grid">
            <% loop $Widgets %>
                <div class="$WrapperClasses"
                     data-widget="$Identifier"
                     data-supports-refresh="<% if $SupportsRefresh %>1<% else %>0<% end_if %>">

                    $RenderedWidget

                </div>
            <% end_loop %>
        </div>
    <% else %>
        <div class="dashboard__empty">
            <span class="font-icon-dashboard"></span>
            <p>No dashboard widgets are available for your account.</p>
        </div>
    <% end_if %>

</div>
