<?php

class ZenBoostPathHelper
{
    private string $pluginRoot;
    private string $projectRoot;

    public function __construct(string $pluginRoot, ?string $projectRoot = null)
    {
        $this->pluginRoot = rtrim($pluginRoot, '/\\') . '/';
        $this->projectRoot = $projectRoot !== null
            ? rtrim($projectRoot, '/\\') . '/'
            : dirname($this->pluginRoot, 3) . '/';
    }

    public static function fromCurrentFile(string $currentFile): self
    {
        $pluginRoot = dirname($currentFile, 2) . '/';

        return new self($pluginRoot);
    }

    public function pluginRoot(): string
    {
        return $this->pluginRoot;
    }

    public function projectRoot(): string
    {
        return $this->projectRoot;
    }

    public function docsCacheDirectory(): string
    {
        return $this->pluginRoot . 'resources/docs-cache/';
    }

    public function catalogsDirectory(): string
    {
        return $this->pluginRoot . 'resources/catalogs/';
    }

    public function guidanceDirectory(): string
    {
        return $this->pluginRoot . 'resources/guidance/';
    }

    public function docsIndexPath(): string
    {
        return $this->catalogsDirectory() . 'docs-index.json';
    }

    public function repoIndexPath(): string
    {
        return $this->catalogsDirectory() . 'repo-index.json';
    }

    public function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    public function slugForUrl(string $url): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? 'index';
        $path = trim($path, '/');
        $path = $path === '' ? 'index' : preg_replace('/[^a-z0-9]+/i', '-', $path);

        return strtolower(trim((string)$path, '-')) . '-' . substr(sha1($url), 0, 10);
    }

    public function listJsonFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = glob(rtrim($directory, '/\\') . '/*.json');

        return $files === false ? [] : $files;
    }
}
