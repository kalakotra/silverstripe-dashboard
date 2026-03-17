<%-- Dashboard/Widgets/ChartWidget.ss --%>
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
        <div class="chart-widget__wrapper" style="height: {$ChartHeight}px; position: relative;">
            <canvas id="$CanvasID" class="chart-widget__canvas"></canvas>
        </div>
    </div>
</div>

<%-- Chart.js CDN --%>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

<script>
(function () {
    var canvas = document.getElementById('$CanvasID');
    if (!canvas) return;

    var chartData    = $ChartDataJSON.RAW;
    var chartOptions = $ChartOptionsJSON.RAW;
    var chartType    = '$ChartType';

    new Chart(canvas, {
        type:    chartType,
        data:    chartData,
        options: chartOptions
    });
})();
</script>
