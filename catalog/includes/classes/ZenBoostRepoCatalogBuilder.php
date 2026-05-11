<?php

class ZenBoostRepoCatalogBuilder
{
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/\\') . '/';
    }

    public function build(): array
    {
        $records = [];
        foreach ($this->targetPaths() as $relativePath) {
            $absolutePath = $this->projectRoot . $relativePath;
            if (is_dir($absolutePath)) {
                foreach ($this->scanDirectory($absolutePath) as $filePath) {
                    $record = $this->recordForFile($filePath);
                    if ($record !== null) {
                        $records[] = $record;
                    }
                }
                continue;
            }

            if (is_file($absolutePath)) {
                $record = $this->recordForFile($absolutePath);
                if ($record !== null) {
                    $records[] = $record;
                }
            }
        }

        return [
            'generated_at' => gmdate('c'),
            'records' => $records,
        ];
    }

    private function targetPaths(): array
    {
        return [
            'includes/application_top.php',
            'includes/classes',
            'includes/init_includes',
            'docs',
            'zc_plugins',
        ];
    }

    private function scanDirectory(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $path = $fileInfo->getPathname();
            if ($this->shouldSkip($path)) {
                continue;
            }

            if (!preg_match('/\.(php|md|txt|html)$/i', $path)) {
                continue;
            }

            $files[] = $path;
        }

        return $files;
    }

    private function shouldSkip(string $path): bool
    {
        foreach (['vendor/', '.git/', 'node_modules/', 'resources/docs-cache/', 'resources/catalogs/'] as $needle) {
            if (str_contains($path, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function recordForFile(string $path): ?array
    {
        $contents = @file_get_contents($path);
        if (!is_string($contents) || trim($contents) === '') {
            return null;
        }

        $relativePath = ltrim(str_replace($this->projectRoot, '', $path), '/');
        $symbols = $this->extractSymbols($contents);

        return [
            'type' => 'repo',
            'path' => $relativePath,
            'title' => basename($path),
            'symbols' => $symbols,
            'excerpt' => $this->excerpt($contents),
            'content' => $contents,
        ];
    }

    private function extractSymbols(string $contents): array
    {
        $symbols = [];

        if (preg_match_all('/^\s*(class|trait|interface)\s+([A-Za-z0-9_]+)/m', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $symbols[] = $match[2];
            }
        }

        if (preg_match_all('/^\s*function\s+([A-Za-z0-9_]+)/m', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $symbols[] = $match[1];
            }
        }

        if (preg_match_all('/^\s*(public|protected|private)\s+function\s+([A-Za-z0-9_]+)/m', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $symbols[] = $match[2];
            }
        }

        return array_values(array_unique($symbols));
    }

    private function excerpt(string $contents): string
    {
        $contents = preg_replace('/\s+/u', ' ', $contents);

        return mb_substr(trim((string)$contents), 0, 240);
    }
}
