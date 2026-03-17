<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Widgets;

/**
 * WidgetWidth
 *
 * Defines the available grid-span widths for a dashboard widget.
 * Maps to CSS classes on the frontend.
 *
 * Grid is 5 columns wide (repeat(5, 1fr)).
 *
 * | Enum case  | CSS class                    | Grid span |
 * |------------|------------------------------|-----------|
 * | Full       | dashboard-widget--full       | span 5    |
 * | Half       | dashboard-widget--half       | span 3*   |
 * | Third      | dashboard-widget--third      | span 2*   |
 * | Quarter    | dashboard-widget--quarter    | span 1-2* |
 * | Fifth      | dashboard-widget--fifth      | span 1    |
 *
 * * Approximate — CSS handles sub-column fractions via the named class.
 */
enum WidgetWidth: string
{
    case Full    = 'full';
    case Half    = 'half';
    case Third   = 'third';
    case Quarter = 'quarter';
    case Fifth   = 'fifth';

    /**
     * Returns the BEM CSS modifier class for this width.
     */
    public function toCssClass(): string
    {
        return 'dashboard-widget--' . $this->value;
    }

    /**
     * Human-readable label (for debugging / CMS previews).
     */
    public function label(): string
    {
        return match ($this) {
            self::Full    => '1/1 – Full width',
            self::Half    => '1/2 – Half width',
            self::Third   => '1/3 – One third',
            self::Quarter => '1/4 – One quarter',
            self::Fifth   => '1/5 – One fifth',
        };
    }

    /**
     * Converts a legacy string constant (WIDTH_FULL etc.) to enum case.
     * Provides backwards-compatible helper for older widget code.
     */
    public static function fromConstant(string $constant): self
    {
        return match (strtoupper($constant)) {
            'WIDTH_FULL'    => self::Full,
            'WIDTH_HALF'    => self::Half,
            'WIDTH_THIRD'   => self::Third,
            'WIDTH_QUARTER' => self::Quarter,
            'WIDTH_FIFTH'   => self::Fifth,
            default         => throw new \InvalidArgumentException("Unknown width constant: {$constant}"),
        };
    }
}
