<%-- Dashboard/Widgets/TableWidget.ss --%>
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
        <% if $HasRows %>
            <div class="table-widget__wrapper">
                <table class="table-widget__table">
                    <thead>
                        <tr>
                            <% loop $Columns %>
                                <th class="table-widget__th">$label</th>
                            <% end_loop %>
                        </tr>
                    </thead>
                    <tbody>
                        <% loop $Rows %>
                            <tr class="table-widget__tr">
                                <% loop $Cells %>
                                    <td class="table-widget__td">
                                        <% if $Link %>
                                            <a href="$Link" class="table-widget__link">$Value</a>
                                        <% else %>
                                            $Value
                                        <% end_if %>
                                    </td>
                                <% end_loop %>
                            </tr>
                        <% end_loop %>
                    </tbody>
                </table>
            </div>

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
                <p>No records found.</p>
            </div>
        <% end_if %>
    </div>
</div>
