<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\Plugins\Console\ZenBoost\Commands;

use Zencart\Console\ConsoleInput;
use Zencart\Console\ConsoleOutput;

class PluginDoctorCommand extends AbstractZenBoostCommand
{
    public function getName(): string
    {
        return 'zen-boost:plugin:doctor';
    }

    public function getDescription(): string
    {
        return 'Run combined Zen Boost checks against a plugin root.';
    }

    public function getAliases(): array
    {
        return ['plugin:doctor'];
    }

    public function getUsageLines(): array
    {
        return [
            'bin/zencart zen-boost:plugin:doctor <path>',
            'php zc_cli.php zen-boost:plugin:doctor <path>',
        ];
    }

    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        $path = $input->getArgument(0, '');
        if ($path === '') {
            $output->errorln('Usage: bin/zencart zen-boost:plugin:doctor <path>');
            return 1;
        }

        $doctor = new \ZenBoostDoctorService($this->paths()->projectRoot());
        $result = $doctor->diagnose($path);

        $output->writeln(($result['ok'] ? 'OK' : 'FAIL') . ': ' . $result['message']);
        $output->writeln('Plugin: ' . (string)($result['plugin_key'] ?? ''));
        $output->writeln('Version: ' . (string)($result['plugin_version'] ?? ''));

        foreach ($result['findings'] ?? [] as $finding) {
            $output->writeln('Finding: ' . $finding);
        }

        foreach ($result['recommendations'] ?? [] as $recommendation) {
            $output->writeln('Recommendation: ' . $recommendation);
        }

        return $result['ok'] ? 0 : 1;
    }
}
