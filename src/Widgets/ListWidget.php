<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\DataList;

/**
 * ListWidget
 *
 * Displays a simple list of items with optional icons and links.
 *
 * Each item array:
 *   [
 *     'title'    => 'Page Title',
 *     'subtitle' => 'Created 2 days ago',   // optional
 *     'link'     => '/admin/pages/edit/1',   // optional
 *     'icon'     => 'font-icon-page',        // optional
 *     'badge'    => 'New',                   // optional badge label
 *     'badgeClass' => 'badge--green',        // optional badge style
 *   ]
 *
 * Usage:
 *   class RecentPagesWidget extends ListWidget
 *   {
 *       protected string $title = 'Recently Edited Pages';
 *       protected int    $order = 20;
 *
 *       protected function getItems(): array
 *       {
 *           return SiteTree::get()
 *               ->sort('LastEdited DESC')
 *               ->limit(8)
 *               ->map('ID', 'Title')
 *               ->toArray();
 *       }
 *   }
 */
class ListWidget extends DashboardWidget
{
    protected string $title = 'List';

    protected int $order = 20;

    protected WidgetWidth $width = WidgetWidth::Third;

    protected string $icon = 'font-icon-list';

    protected int $cacheLifetime = 120;

    protected int $limit = 8;

    protected string $viewAllLink = '';

    protected string $viewAllLabel = 'View all';

    /** Show item icons in the list. */
    protected bool $showIcons = true;

    /**
     * Returns the list of items.
     * Override in subclasses.
     *
     * @return array<int, array<string, string>>|SS_List
     */
    protected function getItems(): SS_List|array
    {
        return [];
    }

    public function getData(): array
    {
        $source = $this->getItems();

        if ($source instanceof DataList) {
            $source = $source->limit($this->limit);
        }

        $items = [];

        foreach ($source as $item) {
            if (is_string($item)) {
                $items[] = [
                    'title'    => $item,
                    'subtitle' => '',
                    'link'     => '',
                    'icon'     => 'font-icon-right-open',
                    'badge'    => '',
                    'badgeClass' => '',
                ];
            } elseif (is_array($item)) {
                $items[] = array_merge([
                    'title'      => '',
                    'subtitle'   => '',
                    'link'       => '',
                    'icon'       => 'font-icon-right-open',
                    'badge'      => '',
                    'badgeClass' => '',
                ], $item);
            } elseif (is_object($item)) {
                $items[] = [
                    'title'    => $item->hasMethod('getTitle') ? $item->getTitle() : (string)$item,
                    'subtitle' => $item->hasMethod('getSubtitle') ? $item->getSubtitle() : '',
                    'link'     => $item->hasMethod('CMSEditLink') ? $item->CMSEditLink() : '',
                    'icon'     => 'font-icon-right-open',
                    'badge'    => '',
                    'badgeClass' => '',
                ];
            }
        }

        return [
            'Items'        => $items,
            'ItemCount'    => count($items),
            'HasItems'     => count($items) > 0,
            'ShowIcons'    => $this->showIcons,
            'ViewAllLink'  => $this->viewAllLink,
            'ViewAllLabel' => $this->viewAllLabel,
        ];
    }
}
