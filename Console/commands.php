<?php

require_once __DIR__ . '/bootstrap.php';

return [
    \Zencart\Plugins\Console\ZenBoost\Commands\DocsFetchCommand::class,
    \Zencart\Plugins\Console\ZenBoost\Commands\CatalogBuildCommand::class,
    \Zencart\Plugins\Console\ZenBoost\Commands\DocsSearchCommand::class,
    \Zencart\Plugins\Console\ZenBoost\Commands\DocsAskCommand::class,
    \Zencart\Plugins\Console\ZenBoost\Commands\DocsCompareCommand::class,
    \Zencart\Plugins\Console\ZenBoost\Commands\ManifestInspectCommand::class,
    \Zencart\Plugins\Console\ZenBoost\Commands\PluginDoctorCommand::class,
    \Zencart\Plugins\Console\ZenBoost\Commands\MakePluginCommand::class,
    \Zencart\Plugins\Console\ZenBoost\Commands\McpServeCommand::class,
];
