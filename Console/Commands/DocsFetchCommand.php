<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\Plugins\Console\ZenBoost\Commands;

use Zencart\Console\ConsoleInput;
use Zencart\Console\ConsoleOutput;

class DocsFetchCommand extends AbstractZenBoostCommand
{
    /**
     * @since ZC v3.0.0
     */
    public function getName(): string
    {
        return 'zen-boost:docs:fetch';
    }

    /**
     * @since ZC v3.0.0
     */
    public function getDescription(): string
    {
        return 'Fetch and cache the configured Zen Cart documentation pages.';
    }

    /**
     * @since ZC v3.0.0
     */
    public function getAliases(): array
    {
        return ['docs:fetch'];
    }

    /**
     * @since ZC v3.0.0
     */
    public function getUsageLines(): array
    {
        return [
            'bin/zencart zen-boost:docs:fetch',
            'php zc_cli.php zen-boost:docs:fetch',
        ];
    }

    /**
     * @since ZC v3.0.0
     */
    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        $fetcher = new \ZenBoostDocFetcher($this->paths(), $this->storage());
        $results = $fetcher->fetchAll(\ZenBoostDocSourceRegistry::all());

        $exitCode = 0;
        foreach ($results as $result) {
            $status = strtoupper((string)($result['status'] ?? 'UNKNOWN'));
            $output->writeln(sprintf('[%s] %s', $status, (string)($result['url'] ?? '')));

            if (!empty($result['reason'])) {
                $output->writeln('  reason: ' . $result['reason']);
            }
            if (!empty($result['file'])) {
                $output->writeln('  file: ' . $result['file']);
            }

            if (($result['status'] ?? '') !== 'ok') {
                $exitCode = 1;
            }
        }

        return $exitCode;
    }
}
