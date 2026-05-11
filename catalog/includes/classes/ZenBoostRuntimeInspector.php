<?php

class ZenBoostRuntimeInspector
{
    private string $projectRoot;
    private string $pluginRoot;

    public function __construct(string $projectRoot, string $pluginRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/\\') . '/';
        $this->pluginRoot = rtrim($pluginRoot, '/\\') . '/';
    }

    public function inspectBootstrapLoaders(): array
    {
        $catalogAutoLoaders = $this->listFiles('includes/auto_loaders');
        $catalogInitIncludes = $this->listFiles('includes/init_includes');
        $adminAutoLoaders = $this->listFiles('admin/includes/auto_loaders');
        $adminInitIncludes = $this->listFiles('admin/includes/init_includes');
        $pluginLoaderFiles = $this->listFilesRelativeToPlugin(['catalog', 'admin'], [
            'extra_configures',
            'extra_datafiles',
            'init_includes',
            'auto_loaders',
            'filenames.php',
        ]);

        return [
            'project_root' => $this->projectRoot,
            'plugin_root' => $this->pluginRoot,
            'catalog' => [
                'auto_loaders' => $catalogAutoLoaders,
                'init_includes' => $catalogInitIncludes,
            ],
            'admin' => [
                'auto_loaders' => $adminAutoLoaders,
                'init_includes' => $adminInitIncludes,
            ],
            'plugin_inputs' => $pluginLoaderFiles,
        ];
    }

    public function lookupFilenameConstant(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['query' => $query, 'matches' => []];
        }

        $matches = [];
        foreach ($this->filenameDefinitionFiles() as $path) {
            $contents = @file_get_contents($path);
            if (!is_string($contents) || $contents === '') {
                continue;
            }

            if (!preg_match_all('/define\(\s*[\'"](FILENAME_[A-Z0-9_]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/', $contents, $rows, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($rows as $row) {
                $constant = $row[1];
                $value = $row[2];
                if (
                    stripos($constant, $query) === false
                    && stripos($value, $query) === false
                ) {
                    continue;
                }

                $matches[] = [
                    'constant' => $constant,
                    'value' => $value,
                    'path' => $this->relativePath($path),
                ];
            }
        }

        return [
            'query' => $query,
            'matches' => $matches,
        ];
    }

    public function listPageModules(string $page): array
    {
        $page = trim($page);
        if ($page === '') {
            return ['page' => $page, 'matches' => []];
        }

        $matches = [];
        foreach ([
            'includes/modules/pages/' . $page,
            'admin/includes/modules/pages/' . $page,
        ] as $relativeDirectory) {
            $absoluteDirectory = $this->projectRoot . $relativeDirectory;
            if (!is_dir($absoluteDirectory)) {
                continue;
            }

            $files = [];
            foreach (glob($absoluteDirectory . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    $files[] = $this->relativePath($file);
                }
            }

            sort($files);

            $matches[] = [
                'directory' => $relativeDirectory,
                'files' => $files,
                'template_candidates' => $this->templateCandidates($page),
            ];
        }

        return [
            'page' => $page,
            'matches' => $matches,
        ];
    }

    public function readRecentLogs(string $pattern = '', int $lineLimit = 40, int $fileLimit = 5): array
    {
        $lineLimit = max(1, $lineLimit);
        $fileLimit = max(1, $fileLimit);
        $pattern = trim($pattern);

        $matches = [];
        foreach ($this->candidateLogFiles() as $path) {
            $basename = basename($path);
            if ($pattern !== '' && stripos($basename, $pattern) === false) {
                continue;
            }

            $lines = @file($path, FILE_IGNORE_NEW_LINES);
            if (!is_array($lines)) {
                continue;
            }

            $tail = array_slice($lines, -$lineLimit);
            $matches[] = [
                'path' => $this->relativePath($path),
                'modified_at' => gmdate('c', filemtime($path) ?: time()),
                'size_bytes' => filesize($path) ?: 0,
                'tail' => $tail,
            ];

            if (count($matches) >= $fileLimit) {
                break;
            }
        }

        return [
            'pattern' => $pattern,
            'line_limit' => $lineLimit,
            'file_limit' => $fileLimit,
            'matches' => $matches,
        ];
    }

    public function listInstalledPlugins(string $statusFilter = 'all'): array
    {
        $statusFilter = strtolower(trim($statusFilter));
        $statusMap = [
            'all' => null,
            'enabled' => 1,
            'disabled' => 2,
            'not-installed' => 0,
            'not_installed' => 0,
        ];

        if (!array_key_exists($statusFilter, $statusMap)) {
            return [
                'status_filter' => $statusFilter,
                'warnings' => ['Unsupported status filter. Use all, enabled, disabled, or not-installed.'],
                'plugins' => [],
            ];
        }

        $context = $this->loadPluginRepositoryContext();
        if (($context['repository'] ?? null) === null) {
            return [
                'status_filter' => $statusFilter,
                'warnings' => $context['warnings'] ?? ['Plugin repository context is unavailable.'],
                'plugins' => [],
            ];
        }

        $repository = $context['repository'];
        $rows = $statusMap[$statusFilter] === null
            ? $repository->getAll()
            : $repository->getInstalledPlugins($statusMap[$statusFilter]);

        $plugins = [];
        foreach ($rows as $row) {
            $uniqueKey = (string)($row['unique_key'] ?? '');
            $version = (string)($row['version'] ?? '');
            $manifestPath = $this->projectRoot . 'zc_plugins/' . $uniqueKey . '/' . $version . '/manifest.php';

            $plugins[] = [
                'unique_key' => $uniqueKey,
                'name' => (string)($row['name'] ?? $uniqueKey),
                'version' => $version,
                'status' => $this->formatPluginStatus((int)($row['status'] ?? 0)),
                'author' => (string)($row['author'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
                'zc_versions' => (string)($row['zc_versions'] ?? ''),
                'manifest_path' => is_file($manifestPath) ? $this->relativePath($manifestPath) : null,
            ];
        }

        usort($plugins, static function (array $left, array $right): int {
            return [(string)$left['name'], (string)$left['unique_key']] <=> [(string)$right['name'], (string)$right['unique_key']];
        });

        return [
            'status_filter' => $statusFilter,
            'warnings' => $context['warnings'] ?? [],
            'plugins' => $plugins,
        ];
    }

    private function filenameDefinitionFiles(): array
    {
        $files = [];
        foreach ([
            'includes/filenames.php',
            'admin/includes/filenames.php',
            'includes/extra_datafiles',
            'admin/includes/extra_datafiles',
            'zc_plugins',
        ] as $relativePath) {
            $absolutePath = $this->projectRoot . $relativePath;
            if (is_file($absolutePath)) {
                $files[] = $absolutePath;
                continue;
            }

            if (!is_dir($absolutePath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absolutePath, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || !preg_match('/filenames\.php$/i', $fileInfo->getFilename())) {
                    continue;
                }

                $files[] = $fileInfo->getPathname();
            }
        }

        sort($files);

        return array_values(array_unique($files));
    }

    private function candidateLogFiles(): array
    {
        $files = [];

        foreach (['logs', 'admin/logs'] as $relativeDirectory) {
            $absoluteDirectory = $this->projectRoot . $relativeDirectory;
            if (!is_dir($absoluteDirectory)) {
                continue;
            }

            foreach (glob($absoluteDirectory . '/*') ?: [] as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $basename = basename($file);
                if (in_array($basename, ['index.html', 'index.php', '.gitignore', '.htaccess'], true)) {
                    continue;
                }

                $files[] = $file;
            }
        }

        usort($files, static function (string $left, string $right): int {
            return (filemtime($right) ?: 0) <=> (filemtime($left) ?: 0);
        });

        return $files;
    }

    private function loadPluginRepositoryContext(): array
    {
        $bootstrap = $this->projectRoot . 'includes/application_cli_bootstrap.php';
        if (!is_file($bootstrap)) {
            return [
                'repository' => null,
                'warnings' => ['CLI bootstrap is unavailable for plugin inspection.'],
            ];
        }

        $capturedWarnings = [];
        $previousErrorReporting = error_reporting();
        error_reporting($previousErrorReporting & ~E_WARNING);

        set_error_handler(static function (int $severity, string $message) use (&$capturedWarnings): bool {
            $capturedWarnings[] = $message;
            return true;
        });

        ob_start();
        try {
            require_once $bootstrap;
            $context = function_exists('zc_cli_get_plugin_repository_context')
                ? zc_cli_get_plugin_repository_context()
                : ['repository' => null, 'warnings' => ['CLI plugin repository helper is unavailable.']];
        } finally {
            $buffer = ob_get_clean();
            restore_error_handler();
            error_reporting($previousErrorReporting);
        }

        if (is_string($buffer) && trim($buffer) !== '') {
            $capturedWarnings[] = trim($buffer);
        }

        $contextWarnings = $context['warnings'] ?? [];
        if (!is_array($contextWarnings)) {
            $contextWarnings = [];
        }

        return [
            'repository' => $context['repository'] ?? null,
            'warnings' => $this->normalizeWarnings(array_merge($contextWarnings, $capturedWarnings)),
        ];
    }

    private function formatPluginStatus(int $status): string
    {
        return match ($status) {
            1 => 'enabled',
            2 => 'disabled',
            0 => 'not-installed',
            default => 'unknown',
        };
    }

    private function normalizeWarnings(array $warnings): array
    {
        $normalized = [];

        foreach ($warnings as $warning) {
            $warning = trim((string)$warning);
            if ($warning === '') {
                continue;
            }

            if (str_starts_with($warning, 'Constant ') && str_contains($warning, ' already defined')) {
                continue;
            }

            $normalized[] = $warning;
        }

        return array_values(array_unique($normalized));
    }

    private function listFiles(string $relativeDirectory): array
    {
        $absoluteDirectory = $this->projectRoot . $relativeDirectory;
        if (!is_dir($absoluteDirectory)) {
            return [];
        }

        $files = [];
        foreach (glob($absoluteDirectory . '/*') ?: [] as $file) {
            if (is_file($file)) {
                $files[] = $this->relativePath($file);
            }
        }

        sort($files);

        return $files;
    }

    private function listFilesRelativeToPlugin(array $sides, array $targets): array
    {
        $results = [];

        foreach ($targets as $target) {
            if ($target === 'filenames.php') {
                $path = $this->pluginRoot . 'filenames.php';
                if (is_file($path)) {
                    $results[] = $this->relativePath($path);
                }
                continue;
            }

            foreach ($sides as $side) {
                $base = $this->pluginRoot . $side . '/includes/' . $target;
                if (!is_dir($base)) {
                    continue;
                }

                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile()) {
                        $results[] = $this->relativePath($fileInfo->getPathname());
                    }
                }
            }
        }

        sort($results);

        return $results;
    }

    private function templateCandidates(string $page): array
    {
        $candidates = [];
        foreach ([
            'includes/templates/template_default/templates/tpl_' . $page . '_default.php',
            'includes/templates/template_default/templates/tpl_' . $page . '.php',
            'includes/templates',
        ] as $relativePath) {
            $absolutePath = $this->projectRoot . $relativePath;
            if (is_file($absolutePath)) {
                $candidates[] = $relativePath;
                continue;
            }

            if ($relativePath !== 'includes/templates' || !is_dir($absolutePath)) {
                continue;
            }

            foreach (glob($absolutePath . '/*/templates/tpl_' . $page . '*.php') ?: [] as $file) {
                if (is_file($file)) {
                    $candidates[] = $this->relativePath($file);
                }
            }
        }

        sort($candidates);

        return array_values(array_unique($candidates));
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace($this->projectRoot, '', $path), '/');
    }
}
