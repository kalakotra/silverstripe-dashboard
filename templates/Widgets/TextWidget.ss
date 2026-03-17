<%-- Dashboard/Widgets/TextWidget.ss --%>
<div class="dashboard-widget__inner">
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__icon $Widget.Icon"></span>
        <h3 class="dashboard-widget__title">$Widget.Title</h3>
    </div>

    <div class="dashboard-widget__body">
        <div class="text-widget__content $ContentClass">
            $Content.RAW
        </div>
    </div>
</div>
