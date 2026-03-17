<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Tasks;

use Kalakotra\Dashboard\Registry\DashboardRegistry;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * DashboardCacheFlushTask
 *
 * Flushes the cached data for every registered dashboard widget.
 *
 * Run via CLI:
 *   vendor/bin/sake dev/tasks/DashboardCacheFlushTask
 *
 * Run via browser (ADMIN only):
 *   /dev/tasks/DashboardCacheFlushTask
 *
 * OPTIONS (query string or CLI args)
 *   widget=StatsWidget   Flush only the named widget (class basename).
 *   verbose=1            Print per-widget status lines (default: 1).
 *
 * CRON EXAMPLE
 *   # Flush dashboard caches every hour
 *   0 * * * * /var/www/html/vendor/bin/sake dev/tasks/DashboardCacheFlushTask quiet=1
 */
class DashboardCacheFlushTask extends BuildTask
{
    private static string $segment = 'DashboardCacheFlushTask';

    protected string $title = 'Dashboard – Flush Widget Caches';

    protected static string $description =
        'Invalidates cached data for all registered dashboard widgets. '
        . 'Pass ?widget=WidgetName to flush only a specific widget.';

    private static bool $is_enabled = true;

    // -------------------------------------------------------------------------
    // Task entry point
    // -------------------------------------------------------------------------

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $verbose = !$output->isQuiet();
        $targetWidget = $this->getInputOption($input, 'widget');

        $this->output('', $output);
        $this->output('=======================================', $output);
        $this->output(' Dashboard Cache Flush Task', $output);
        $this->output('=======================================', $output);

        if ($targetWidget) {
            $this->flushSingleWidget($targetWidget, $output, $verbose);
        } else {
            $this->flushAllWidgets($output, $verbose);
        }

        $this->output('', $output);
        $this->output('Done.', $output);
        $this->output('', $output);

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Flush strategies
    // -------------------------------------------------------------------------

    /**
     * Flushes the cache of every registered widget.
     */
    private function flushAllWidgets(PolyOutput $output, bool $verbose): void
    {
        $registry = DashboardRegistry::singleton();
        $widgets  = $registry->getWidgets();

        if (empty($widgets)) {
            $this->output('No widgets registered.', $output);
            return;
        }

        $flushed  = 0;
        $skipped  = 0;

        foreach ($widgets as $widget) {
            $identifier = $widget->getIdentifier();

            if ($widget->getCacheLifetime() === 0) {
                if ($verbose) {
                    $this->output("  SKIP   {$identifier} (caching disabled)", $output);
                }
                $skipped++;
                continue;
            }

            $widget->invalidateCache();

            if ($verbose) {
                $this->output("  FLUSH  {$identifier}", $output);
            }

            $flushed++;
        }

        $this->output('', $output);
        $this->output("Flushed : {$flushed} widget(s)", $output);
        $this->output("Skipped : {$skipped} widget(s) (no cache)", $output);
    }

    /**
     * Flushes a single named widget.
     */
    private function flushSingleWidget(string $identifier, PolyOutput $output, bool $verbose): void
    {
        $registry = DashboardRegistry::singleton();
        $widget   = $registry->findByIdentifier($identifier);

        if (!$widget) {
            $this->output("ERROR: Widget '{$identifier}' not found in registry.", $output);
            $this->output('Registered widgets:', $output);

            foreach ($registry->getWidgets() as $w) {
                $this->output('  - ' . $w->getIdentifier(), $output);
            }

            return;
        }

        if ($widget->getCacheLifetime() === 0) {
            $this->output("SKIP: {$identifier} has caching disabled.", $output);
            return;
        }

        $widget->invalidateCache();
        if ($verbose) {
            $this->output("FLUSH: {$identifier} cache cleared.", $output);
        }
    }

    // -------------------------------------------------------------------------
    // Output helper – handles CLI vs. browser formatting
    // -------------------------------------------------------------------------

    private function output(string $line, PolyOutput $output): void
    {
        $output->writeln($line);
    }

    private function getInputOption(InputInterface $input, string $name): ?string
    {
        $value = $input->getParameterOption("--{$name}", null, true);
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
