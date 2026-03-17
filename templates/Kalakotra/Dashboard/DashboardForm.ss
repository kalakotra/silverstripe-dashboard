<%-- Dashboard/DashboardForm.ss
     Injected into LeftAndMain::EditForm() slot.
     Replaces the default form chrome with the bare dashboard grid.
     LeftAndMain still wraps this with breadcrumbs + menu. --%>

<div class="cms-edit-form dashboard-form" id="Form_EditForm">

    <%-- CMS breadcrumb / title bar --%>
    <div class="cms-content-header">
        <div class="cms-content-header-info flexbox-area-grow">
            <div class="cms-breadcrumbs">
                <span>Dashboard</span>
            </div>
        </div>
        <div class="cms-content-header-tabs cms-tabset-nav-primary"></div>
    </div>

    <%-- Dashboard body --%>
    <div class="cms-content-view">
        <div class="dashboard" id="dashboard-root">

            <div class="dashboard__header">
                <h1 class="dashboard__title">
                    <span class="font-icon-dashboard"></span>
                    Dashboard
                </h1>
                <div class="dashboard__actions">
                    <button class="dashboard__refresh-all btn btn-outline-secondary btn-sm"
                            type="button"
                            aria-label="Refresh all widgets">
                        <span class="font-icon-refresh"></span>
                        Refresh
                    </button>
                </div>
            </div>

            <% with $FormData %>
                <% if $Widgets %>
                    <div class="dashboard__grid" id="dashboard-grid">
                        <% loop $Widgets %>
                            <div class="{$WrapperClasses}"
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
            <% end_with %>

        </div><%-- /.dashboard --%>
    </div><%-- /.cms-content-view --%>

</div><%-- /#Form_EditForm --%>
