<?php

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    /**
     * @since ZC v3.0.0
     */
    protected function validateInstall(): bool
    {
        if ($this->isConsoleAvailable()) {
            return true;
        }

        $message = defined('ERROR_ZEN_BOOST_CONSOLE_REQUIRED')
            ? ERROR_ZEN_BOOST_CONSOLE_REQUIRED
            : 'Zen Boost requires the shared Zen Cart command console (`bin/zencart`) and its core console classes. Install a Zen Cart build that includes the console framework before installing this plugin.';

        $this->errorContainer->addError(0, $message, false, $message);

        return false;
    }

    protected function executeInstall()
    {
        zen_deregister_admin_pages(['toolsZenBoost']);
        zen_register_admin_page('toolsZenBoost', 'BOX_TOOLS_ZEN_BOOST', 'FILENAME_ZEN_BOOST', '', 'tools', 'Y', 200);

        return true;
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages(['toolsZenBoost']);

        return true;
    }

    /**
     * @since ZC v3.0.0
     */
    protected function isConsoleAvailable(): bool
    {
        $requiredPaths = [
            DIR_FS_CATALOG . 'bin/zencart',
            DIR_FS_CATALOG . 'zc_cli.php',
            DIR_FS_CATALOG . 'includes/classes/Console/ConsoleKernel.php',
            DIR_FS_CATALOG . 'includes/classes/Console/ConsoleCommand.php',
            DIR_FS_CATALOG . 'includes/classes/Console/PluginCommandDiscovery.php',
        ];

        foreach ($requiredPaths as $path) {
            if (!is_file($path)) {
                return false;
            }
        }

        return true;
    }
}
