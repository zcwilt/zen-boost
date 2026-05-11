<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\Plugins\Console\ZenBoost\Commands;

use Zencart\Console\ConsoleInput;
use Zencart\Console\ConsoleOutput;

class CatalogBuildCommand extends AbstractZenBoostCommand
{
    /**
     * @since ZC v3.0.0
     */
    public function getName(): string
    {
        return 'zen-boost:catalog:build';
    }

    /**
     * @since ZC v3.0.0
     */
    public function getDescription(): string
    {
        return 'Build the Zen Boost docs and repository JSON catalogs.';
    }

    /**
     * @since ZC v3.0.0
     */
    public function getAliases(): array
    {
        return ['catalog:build'];
    }

    /**
     * @since ZC v3.0.0
     */
    public function getUsageLines(): array
    {
        return [
            'bin/zencart zen-boost:catalog:build',
            'php zc_cli.php zen-boost:catalog:build',
        ];
    }

    /**
     * @since ZC v3.0.0
     */
    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        $paths = $this->paths();
        $storage = $this->storage();
        $documents = [];

        foreach ($paths->listJsonFiles($paths->docsCacheDirectory()) as $filePath) {
            $document = $storage->readJsonFile($filePath);
            if ($document !== []) {
                $documents[] = $document;
            }
        }

        $chunker = new \ZenBoostDocChunker();
        $docsIndex = $chunker->buildIndex($documents);
        $storage->writeJsonFile($paths->docsIndexPath(), $docsIndex);

        $repoBuilder = new \ZenBoostRepoCatalogBuilder($paths->projectRoot());
        $repoIndex = $repoBuilder->build();
        $storage->writeJsonFile($paths->repoIndexPath(), $repoIndex);

        $output->writeln('Docs chunks: ' . count($docsIndex['chunks'] ?? []));
        $output->writeln('Repo records: ' . count($repoIndex['records'] ?? []));

        return 0;
    }
}
