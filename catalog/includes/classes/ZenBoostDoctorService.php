<?php

class ZenBoostDoctorService
{
    private string $projectRoot;
    private ZenBoostManifestInspector $manifestInspector;
    private ZenBoostInstallerInspector $installerInspector;
    private ZenBoostRuntimeInspector $runtimeInspector;

    public function __construct(
        string $projectRoot,
        ?ZenBoostManifestInspector $manifestInspector = null,
        ?ZenBoostInstallerInspector $installerInspector = null,
        ?ZenBoostRuntimeInspector $runtimeInspector = null
    ) {
        $this->projectRoot = rtrim($projectRoot, '/\\') . '/';
        $this->manifestInspector = $manifestInspector ?? new ZenBoostManifestInspector();
        $this->installerInspector = $installerInspector ?? new ZenBoostInstallerInspector();
        $this->runtimeInspector = $runtimeInspector ?? new ZenBoostRuntimeInspector($this->projectRoot, $this->projectRoot . 'zc_plugins/zen-boost/v1.0.0/');
    }

    public function diagnose(string $path): array
    {
        $pluginRoot = $this->normalizePluginRoot($path);
        if ($pluginRoot === null) {
            return [
                'ok' => false,
                'message' => 'Plugin root could not be resolved.',
                'input' => $path,
                'checks' => [],
                'findings' => ['The provided path does not resolve to a plugin root with a manifest.'],
                'recommendations' => ['Pass a plugin root, manifest path, or Installer path.'],
            ];
        }

        $manifestPath = $pluginRoot . 'manifest.php';
        $manifest = $this->manifestInspector->inspect($manifestPath);
        $installer = $this->installerInspector->inspect($pluginRoot);

        [$pluginKey, $pluginVersion] = $this->resolvePluginIdentity($pluginRoot, $manifest);
        $installedState = $this->matchInstalledPlugin($pluginKey, $pluginVersion);
        $filenameLookup = $this->runtimeInspector->lookupFilenameConstant($pluginKey);
        $structure = $this->inspectStructure($pluginRoot);

        $findings = [];
        if (!($manifest['ok'] ?? false)) {
            foreach ($manifest['missing'] ?? [] as $missing) {
                $findings[] = 'Manifest is missing `' . $missing . '`.';
            }
        }
        foreach ($installer['findings'] ?? [] as $finding) {
            $findings[] = $finding;
        }
        if (($installedState['status'] ?? null) === 'missing') {
            $findings[] = 'Plugin is not present in plugin manager state or the plugin list is unavailable.';
        }
        if (($installedState['status'] ?? null) === 'version-mismatch') {
            $findings[] = 'Plugin manager state points to version `' . ($installedState['installed_version'] ?? '') . '` instead of `' . $pluginVersion . '`.';
        }
        foreach ($structure['findings'] ?? [] as $finding) {
            $findings[] = $finding;
        }

        $recommendations = $this->buildRecommendations($manifest, $installer, $installedState, $structure);

        return [
            'ok' => $findings === [],
            'message' => $findings === [] ? 'Plugin passed the current Zen Boost doctor checks.' : 'Plugin has one or more issues to address.',
            'plugin_root' => $pluginRoot,
            'plugin_key' => $pluginKey,
            'plugin_version' => $pluginVersion,
            'checks' => [
                'manifest' => $manifest,
                'installer' => $installer,
                'installed_state' => $installedState,
                'filename_lookup' => $filenameLookup,
                'structure' => $structure,
            ],
            'findings' => $findings,
            'recommendations' => $recommendations,
        ];
    }

    private function normalizePluginRoot(string $path): ?string
    {
        $resolved = realpath($path);
        if ($resolved === false) {
            return null;
        }

        if (is_file($resolved)) {
            $resolved = dirname($resolved);
        }

        $resolved = rtrim($resolved, '/\\') . '/';
        if (basename(rtrim($resolved, '/\\')) === 'Installer') {
            $resolved = dirname(rtrim($resolved, '/\\')) . '/';
        }

        if (is_file($resolved . 'manifest.php')) {
            return $resolved;
        }

        return null;
    }

    private function matchInstalledPlugin(string $pluginKey, string $pluginVersion): array
    {
        $plugins = $this->runtimeInspector->listInstalledPlugins('all');
        $warnings = $plugins['warnings'] ?? [];

        foreach ($plugins['plugins'] ?? [] as $plugin) {
            if (($plugin['unique_key'] ?? '') !== $pluginKey) {
                continue;
            }

            $installedVersion = (string)($plugin['version'] ?? '');

            return [
                'status' => $installedVersion === $pluginVersion ? 'found' : 'version-mismatch',
                'plugin' => $plugin,
                'warnings' => $warnings,
                'installed_version' => $installedVersion,
            ];
        }

        return [
            'status' => 'missing',
            'warnings' => $warnings,
        ];
    }

    private function resolvePluginIdentity(string $pluginRoot, array $manifest): array
    {
        $normalizedRoot = rtrim($pluginRoot, '/\\');
        $segments = preg_split('#[\\\\/]#', $normalizedRoot) ?: [];
        $zcPluginsIndex = array_search('zc_plugins', $segments, true);

        if ($zcPluginsIndex !== false && isset($segments[$zcPluginsIndex + 1], $segments[$zcPluginsIndex + 2])) {
            return [$segments[$zcPluginsIndex + 1], $segments[$zcPluginsIndex + 2]];
        }

        $manifestArray = is_array($manifest['manifest'] ?? null) ? $manifest['manifest'] : [];
        $pluginVersion = (string)($manifestArray['pluginVersion'] ?? basename($normalizedRoot));
        $pluginName = (string)($manifestArray['pluginName'] ?? basename($normalizedRoot));
        $pluginKey = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $pluginName) ?? '', '-'));

        if ($pluginKey === '') {
            $pluginKey = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', basename($normalizedRoot)) ?? '', '-'));
        }

        return [$pluginKey, $pluginVersion];
    }

    private function buildRecommendations(array $manifest, array $installer, array $installedState, array $structure): array
    {
        $recommendations = [];

        if (!($manifest['ok'] ?? false)) {
            $recommendations[] = 'Complete the manifest baseline fields before relying on plugin manager workflows.';
        }
        if (!($installer['ok'] ?? false)) {
            $recommendations[] = 'Add the missing installer structure and language files so install and uninstall behavior is explicit.';
        }
        if (($installedState['status'] ?? '') === 'missing') {
            $recommendations[] = 'Install or enable the plugin through Plugin Manager if you want bootstrap discovery and runtime loading.';
        }
        if (($structure['findings'] ?? []) !== []) {
            $recommendations[] = 'Add the missing page, language, or template files so the plugin surface is complete.';
        }
        if ($recommendations === []) {
            $recommendations[] = 'Next, verify page modules, language files, and any runtime logs for the plugin in a live checkout.';
        }

        return array_values(array_unique($recommendations));
    }

    private function inspectStructure(string $pluginRoot): array
    {
        $catalogPages = [];
        $adminPages = [];
        $findings = [];

        $catalogPagesRoot = $pluginRoot . 'catalog/includes/modules/pages/';
        if (is_dir($catalogPagesRoot)) {
            foreach (glob($catalogPagesRoot . '*', GLOB_ONLYDIR) ?: [] as $pageDirectory) {
                $page = basename($pageDirectory);
                $headerPath = $pageDirectory . '/header_php.php';
                $languagePath = $pluginRoot . 'catalog/includes/languages/english/lang.' . $page . '.php';
                $templateFiles = glob($pluginRoot . 'catalog/includes/templates/*/tpl_' . $page . '*.php') ?: [];

                $catalogPages[] = [
                    'page' => $page,
                    'header_php' => is_file($headerPath) ? $headerPath : null,
                    'language_file' => is_file($languagePath) ? $languagePath : null,
                    'template_files' => $templateFiles,
                ];

                if (!is_file($headerPath)) {
                    $findings[] = 'Catalog page `' . $page . '` is missing `header_php.php`.';
                }
                if (!is_file($languagePath)) {
                    $findings[] = 'Catalog page `' . $page . '` is missing `catalog/includes/languages/english/lang.' . $page . '.php`.';
                }
                if ($templateFiles === []) {
                    $findings[] = 'Catalog page `' . $page . '` is missing a matching template file.';
                }
            }
        }

        $adminRoot = $pluginRoot . 'admin/';
        if (is_dir($adminRoot)) {
            foreach (glob($adminRoot . '*.php') ?: [] as $adminPagePath) {
                $page = basename($adminPagePath, '.php');
                $languagePath = $pluginRoot . 'admin/includes/languages/english/lang.' . $page . '.php';

                $adminPages[] = [
                    'page' => $page,
                    'entrypoint' => $adminPagePath,
                    'language_file' => is_file($languagePath) ? $languagePath : null,
                ];

                if (!is_file($languagePath)) {
                    $findings[] = 'Admin page `' . $page . '` is missing `admin/includes/languages/english/lang.' . $page . '.php`.';
                }
            }
        }

        return [
            'catalog_pages' => $catalogPages,
            'admin_pages' => $adminPages,
            'findings' => $findings,
        ];
    }
}
