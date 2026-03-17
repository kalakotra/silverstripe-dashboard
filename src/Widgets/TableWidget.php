<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\DataList;

/**
 * TableWidget
 *
 * Displays a data table with configurable columns and rows.
 *
 * Column definition format:
 *   [
 *     'field'  => 'Title',     // DataObject field name or key in row array
 *     'label'  => 'Name',      // Column header label
 *     'link'   => 'CMSEditLink', // optional: method to call for row link
 *   ]
 *
 * Usage:
 *   class RecentOrdersWidget extends TableWidget
 *   {
 *       protected string $title = 'Recent Orders';
 *       protected int    $order = 30;
 *       protected WidgetWidth $width = WidgetWidth::Half;
 *
 *       protected function getColumns(): array
 *       {
 *           return [
 *               ['field' => 'Title',       'label' => 'Order'],
 *               ['field' => 'Status',      'label' => 'Status'],
 *               ['field' => 'TotalAmount', 'label' => 'Total'],
 *           ];
 *       }
 *
 *       protected function getRows(): SS_List|array
 *       {
 *           return Order::get()->sort('Created DESC')->limit(10);
 *       }
 *   }
 */
class TableWidget extends DashboardWidget
{
    protected string $title = 'Table';

    protected int $order = 30;

    protected WidgetWidth $width = WidgetWidth::Half;

    protected string $icon = 'font-icon-list';

    protected int $cacheLifetime = 120;

    /** Maximum rows to display. */
    protected int $limit = 10;

    /** Show a "View all" link at the bottom. */
    protected string $viewAllLink = '';

    /** Label for the view-all link. */
    protected string $viewAllLabel = 'View all';

    /**
     * Column definitions.
     * Override in subclasses.
     *
     * @return array<int, array<string, string>>
     */
    protected function getColumns(): array
    {
        return [
            ['field' => 'Title', 'label' => 'Title'],
        ];
    }

    /**
     * Data source – DataList or plain array.
     * Override in subclasses.
     *
     * @return SS_List|array<int, array<string, mixed>>
     */
    protected function getRows(): SS_List|array
    {
        return [];
    }

    public function getData(): array
    {
        $columns = $this->getColumns();
        $source  = $this->getRows();

        // Normalise to array of rows
        $rows = [];

        if ($source instanceof DataList) {
            $source = $source->limit($this->limit);
        }

        foreach ($source as $item) {
            $cells = [];

            foreach ($columns as $col) {
                $field = $col['field'];

                if (is_array($item)) {
                    $value = $item[$field] ?? '';
                } elseif (is_object($item)) {
                    $value = $item->hasMethod($field)
                        ? $item->{$field}()
                        : ($item->{$field} ?? '');
                } else {
                    $value = '';
                }

                $link = null;
                if (!empty($col['link']) && is_object($item) && $item->hasMethod($col['link'])) {
                    $link = $item->{$col['link']}();
                }

                $cells[] = [
                    'Value' => (string) $value,
                    'Link'  => $link,
                ];
            }

            $rows[] = ['Cells' => $cells];
        }

        return [
            'Columns'     => $columns,
            'Rows'        => $rows,
            'RowCount'    => count($rows),
            'HasRows'     => count($rows) > 0,
            'ViewAllLink' => $this->viewAllLink,
            'ViewAllLabel' => $this->viewAllLabel,
            'Limit'       => $this->limit,
        ];
    }
}
