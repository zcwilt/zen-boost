<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\Plugins\Console\ZenBoost\Commands;

use Zencart\Console\ConsoleCommand;

abstract class AbstractZenBoostCommand extends ConsoleCommand
{
    /**
     * @since ZC v3.0.0
     */
    protected function pluginRoot(): string
    {
        return dirname(__DIR__, 2) . '/';
    }

    /**
     * @since ZC v3.0.0
     */
    protected function paths(): \ZenBoostPathHelper
    {
        return new \ZenBoostPathHelper($this->pluginRoot());
    }

    /**
     * @since ZC v3.0.0
     */
    protected function storage(): \ZenBoostJsonStorage
    {
        return new \ZenBoostJsonStorage();
    }
}
