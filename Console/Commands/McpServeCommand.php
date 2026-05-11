<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\Plugins\Console\ZenBoost\Commands;

use Zencart\Console\ConsoleInput;
use Zencart\Console\ConsoleOutput;

class McpServeCommand extends AbstractZenBoostCommand
{
    /**
     * @since ZC v3.0.0
     */
    public function getName(): string
    {
        return 'zen-boost:mcp:serve';
    }

    /**
     * @since ZC v3.0.0
     */
    public function getDescription(): string
    {
        return 'Run the Zen Boost MCP server over stdio.';
    }

    /**
     * @since ZC v3.0.0
     */
    public function getAliases(): array
    {
        return ['mcp:serve'];
    }

    /**
     * @since ZC v3.0.0
     */
    public function getUsageLines(): array
    {
        return [
            'bin/zencart zen-boost:mcp:serve',
            'php zc_cli.php zen-boost:mcp:serve',
        ];
    }

    /**
     * @since ZC v3.0.0
     */
    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        unset($input, $output);

        $server = new \ZenBoostMcpServer($this->paths());

        return $server->run();
    }
}
