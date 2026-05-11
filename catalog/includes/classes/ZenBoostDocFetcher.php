<?php

class ZenBoostDocFetcher
{
    private ZenBoostPathHelper $paths;
    private ZenBoostJsonStorage $storage;

    public function __construct(ZenBoostPathHelper $paths, ZenBoostJsonStorage $storage)
    {
        $this->paths = $paths;
        $this->storage = $storage;
    }

    public function fetchAll(array $sources): array
    {
        $results = [];
        $this->paths->ensureDirectory($this->paths->docsCacheDirectory());

        foreach ($sources as $source) {
            $results[] = $this->fetchOne($source);
        }

        return $results;
    }

    public function fetchOne(array $source): array
    {
        $url = (string)($source['url'] ?? '');
        $tags = isset($source['tags']) && is_array($source['tags']) ? array_values($source['tags']) : [];

        if ($url === '') {
            return ['url' => $url, 'status' => 'skipped', 'reason' => 'empty-url'];
        }

        $response = $this->download($url);
        if ($response['ok'] !== true) {
            return [
                'url' => $url,
                'status' => 'failed',
                'reason' => $response['error'],
            ];
        }

        $document = $this->parseDocument($url, $response['body'], $tags, $response['headers']);
        $filePath = $this->paths->docsCacheDirectory() . $this->paths->slugForUrl($url) . '.json';
        $this->storage->writeJsonFile($filePath, $document);

        return [
            'url' => $url,
            'status' => 'ok',
            'file' => $filePath,
            'title' => $document['title'] ?? '',
        ];
    }

    private function download(string $url): array
    {
        if (function_exists('curl_init')) {
            $headers = [];
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'ZenBoost/1.0',
                CURLOPT_HEADERFUNCTION => static function ($curl, $headerLine) use (&$headers) {
                    $length = strlen($headerLine);
                    $parts = explode(':', $headerLine, 2);
                    if (count($parts) === 2) {
                        $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                    }

                    return $length;
                },
            ]);
            $body = curl_exec($ch);
            $error = curl_error($ch);
            $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if (!is_string($body) || $body === '') {
                return ['ok' => false, 'error' => $error !== '' ? $error : 'empty-response'];
            }

            if ($statusCode >= 400) {
                return ['ok' => false, 'error' => 'http-' . $statusCode];
            }

            return ['ok' => true, 'body' => $body, 'headers' => $headers];
        }

        $body = @file_get_contents($url);
        if (!is_string($body) || $body === '') {
            return ['ok' => false, 'error' => 'download-failed'];
        }

        return ['ok' => true, 'body' => $body, 'headers' => []];
    }

    private function parseDocument(string $url, string $html, array $tags, array $headers): array
    {
        $title = $this->extractTitle($html);
        $text = $this->normalizeWhitespace(strip_tags($html));

        return [
            'url' => $url,
            'title' => $title,
            'tags' => $tags,
            'fetched_at' => gmdate('c'),
            'last_modified' => $headers['last-modified'] ?? '',
            'html' => $html,
            'text' => $text,
        ];
    }

    private function extractTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches) === 1) {
            return html_entity_decode(trim(strip_tags($matches[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return '';
    }

    private function normalizeWhitespace(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string)$text);
    }
}
