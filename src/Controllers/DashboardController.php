<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Controllers;

use Kalakotra\Dashboard\Forms\DashboardForm;
use Kalakotra\Dashboard\Registry\DashboardRegistry;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

/**
 * DashboardController
 *
 * Full LeftAndMain CMS section for the Dashboard module.
 *
 * URL segment : /admin/dashboard/
 * Menu entry  : Dashboard (priority 100 → top of menu)
 * Template    : Kalakotra/Dashboard/DashboardForm.ss
 * AJAX refresh: GET /admin/dashboard/widgetRefresh/{Widget}
 */
class DashboardController extends LeftAndMain implements PermissionProvider
{
    // -------------------------------------------------------------------------
    // Static configuration
    // -------------------------------------------------------------------------

    private static $url_segment = 'dashboard';

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $menu_title = 'Dashboard';

    private static $menu_icon_class = 'font-icon-dashboard';

    /** Higher value = higher up in the CMS menu. */
    private static $menu_priority = 100;

    private static $required_permission_codes = 'CMS_ACCESS_Dashboard';

    private static $allowed_actions = [
        'index',
        'widgetRefresh',
    ];

    // -------------------------------------------------------------------------
    // LeftAndMain overrides
    // -------------------------------------------------------------------------

    public function index(HTTPRequest $request): HTTPResponse
    {
        $this->loadRequirements();

        return parent::index($request);
    }

    /**
     * Builds the "form" rendered in the CMS content area.
     *
    * We satisfy LeftAndMain's Form contract while delegating
    * all actual rendering to Kalakotra/Dashboard/DashboardForm.ss.
     */
    public function getEditForm($id = null, $fields = null): Form
    {
        $this->loadRequirements();

        $member  = Security::getCurrentUser();
        $widgets = $this->getRegistry()->getVisibleWidgets($member);

        $widgetList = ArrayList::create();

        foreach ($widgets as $widget) {
            $widgetList->push(ArrayData::create([
                'Widget'          => $widget,
                'RenderedWidget'  => $widget->render(),
                'WrapperClasses'  => $widget->getWrapperClasses(),
                'Identifier'      => $widget->getIdentifier(),
                'SupportsRefresh' => $widget->supportsRefresh(),
            ]));
        }

        $form = DashboardForm::create(
            $this,
            'EditForm',
            FieldList::create(),
            FieldList::create(
                FormAction::create('doSave', 'Save')->setUseButtonTag(true)
            )
        );

        $form->addExtraClass('dashboard-form');
        $form->setTemplate('Kalakotra/Dashboard/DashboardForm');
        $form->setFormData([
            'Widgets'     => $widgetList,
            'WidgetCount' => $widgetList->count(),
        ]);
        $form->disableSecurityToken();

        $this->extend('updateEditForm', $form);

        return $form;
    }

    public function SectionTitle(): string
    {
        return 'Dashboard';
    }

    public function LinkPreview(): string
    {
        return '';
    }

    public function getCMSActions(): FieldList
    {
        return FieldList::create();
    }

    // -------------------------------------------------------------------------
    // AJAX refresh endpoint
    // -------------------------------------------------------------------------

    /**
     * Refreshes a single widget via AJAX.
     *
     * GET /admin/dashboard/widgetRefresh/{Widget}
     *
     * 200: { html: string, identifier: string }
     * 4xx: { error: string }
     */
    public function widgetRefresh(HTTPRequest $request): HTTPResponse
    {
        $identifier = (string) $request->param('ID');
        $member     = Security::getCurrentUser();

        if (!$member) {
            return $this->dashboardJsonError('Unauthorized', 401);
        }

        if (!$identifier) {
            return $this->dashboardJsonError('Missing widget identifier', 400);
        }

        $widget = $this->getRegistry()->findByIdentifier($identifier);

        if (!$widget) {
            return $this->dashboardJsonError("Widget '{$identifier}' not found", 404);
        }

        if (!$widget->canView($member)) {
            return $this->dashboardJsonError('Access denied', 403);
        }

        if (!$widget->supportsRefresh()) {
            return $this->dashboardJsonError('Widget does not support refresh', 400);
        }

        $widget->invalidateCache();

        return $this->dashboardJsonResponse([
            'html'       => (string) $widget->render(),
            'identifier' => $identifier,
        ]);
    }

    // -------------------------------------------------------------------------
    // Permissions
    // -------------------------------------------------------------------------

    public function providePermissions(): array
    {
        return [
            'CMS_ACCESS_Dashboard' => [
                'name'     => 'Access Dashboard section',
                'category' => 'CMS Access',
                'help'     => 'Grants access to the CMS Dashboard overview.',
                'sort'     => 100,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    protected function getRegistry(): DashboardRegistry
    {
        return DashboardRegistry::singleton();
    }

    protected function loadRequirements(): void
    {
        Requirements::css('kalakotra/silverstripe-dashboard:client/css/dashboard.css');
        Requirements::javascript('kalakotra/silverstripe-dashboard:client/js/dashboard.js');
        Requirements::set_force_js_to_bottom(true);
    }

    private function dashboardJsonResponse(array $data, int $status = 200): HTTPResponse
    {
        return HTTPResponse::create()
            ->setStatusCode($status)
            ->addHeader('Content-Type', 'application/json; charset=utf-8')
            ->addHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string) json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private function dashboardJsonError(string $message, int $status): HTTPResponse
    {
        return $this->dashboardJsonResponse(['error' => $message], $status);
    }
}
