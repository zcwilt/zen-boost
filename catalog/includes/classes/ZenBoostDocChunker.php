<?php

class ZenBoostDocChunker
{
    public function buildIndex(array $documents): array
    {
        $chunks = [];

        foreach ($documents as $document) {
            foreach ($this->chunkDocument($document) as $chunk) {
                $chunks[] = $chunk;
            }
        }

        return [
            'generated_at' => gmdate('c'),
            'chunks' => $chunks,
        ];
    }

    public function chunkDocument(array $document): array
    {
        $html = (string)($document['html'] ?? '');
        if ($html === '') {
            return [];
        }

        if (!class_exists('DOMDocument')) {
            return [[
                'type' => 'docs',
                'url' => $document['url'] ?? '',
                'title' => $document['title'] ?? '',
                'heading_path' => [$document['title'] ?? 'Document'],
                'tags' => $document['tags'] ?? [],
                'excerpt' => $this->excerpt((string)($document['text'] ?? '')),
                'content' => (string)($document['text'] ?? ''),
            ]];
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return [];
        }

        $chunks = [];
        $currentHeading = $document['title'] ?? 'Document';
        $buffer = [];

        foreach ($body->childNodes as $node) {
            $nodeName = strtolower($node->nodeName);
            if (in_array($nodeName, ['h1', 'h2', 'h3', 'h4'], true)) {
                $chunks = $this->flushChunk($chunks, $document, $currentHeading, $buffer);
                $currentHeading = $this->normalizeWhitespace($node->textContent);
                continue;
            }

            $text = $this->normalizeWhitespace($node->textContent);
            if ($text !== '') {
                $buffer[] = $text;
            }
        }

        return $this->flushChunk($chunks, $document, $currentHeading, $buffer);
    }

    private function flushChunk(array $chunks, array $document, string $heading, array &$buffer): array
    {
        $content = trim(implode("\n\n", $buffer));
        $buffer = [];

        if ($content === '') {
            return $chunks;
        }

        $chunks[] = [
            'type' => 'docs',
            'url' => $document['url'] ?? '',
            'title' => $document['title'] ?? '',
            'heading_path' => [$heading],
            'tags' => $document['tags'] ?? [],
            'excerpt' => $this->excerpt($content),
            'content' => $content,
        ];

        return $chunks;
    }

    private function excerpt(string $content): string
    {
        return mb_substr($content, 0, 240);
    }

    private function normalizeWhitespace(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string)$text);
    }
}
