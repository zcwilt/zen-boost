<?php

class ZenBoostSearchService
{
    public function searchDocs(array $docsIndex, string $query, int $limit = 10): array
    {
        return $this->searchRecords($docsIndex['chunks'] ?? [], $query, $limit, 'docs');
    }

    public function searchRepo(array $repoIndex, string $query, int $limit = 10): array
    {
        return $this->searchRecords($repoIndex['records'] ?? [], $query, $limit, 'repo');
    }

    public function search(array $docsIndex, array $repoIndex, string $query, int $limit = 10): array
    {
        $results = array_merge(
            $this->searchDocs($docsIndex, $query, PHP_INT_MAX),
            $this->searchRepo($repoIndex, $query, PHP_INT_MAX)
        );

        usort($results, static function (array $left, array $right): int {
            return $right['_score'] <=> $left['_score'];
        });

        return array_slice($results, 0, $limit);
    }

    private function searchRecords(array $records, string $query, int $limit, string $type): array
    {
        $terms = $this->terms($query);
        $results = [];

        foreach ($records as $record) {
            $score = $this->scoreRecord($record, $terms, $type);
            if ($score <= 0) {
                continue;
            }

            $record['_score'] = $score;
            $results[] = $record;
        }

        usort($results, static function (array $left, array $right): int {
            return $right['_score'] <=> $left['_score'];
        });

        return array_slice($results, 0, $limit);
    }

    private function scoreRecord(array $record, array $terms, string $type): int
    {
        $score = 0;
        $title = mb_strtolower((string)($record['title'] ?? ''));
        $content = mb_strtolower((string)($record['content'] ?? ''));
        $excerpt = mb_strtolower((string)($record['excerpt'] ?? ''));
        $path = mb_strtolower((string)($record['path'] ?? ''));
        $heading = mb_strtolower(implode(' ', $record['heading_path'] ?? []));
        $tags = mb_strtolower(implode(' ', $record['tags'] ?? []));
        $symbols = mb_strtolower(implode(' ', $record['symbols'] ?? []));

        foreach ($terms as $term) {
            if ($term === '') {
                continue;
            }

            if (str_contains($title, $term)) {
                $score += 10;
            }
            if (str_contains($heading, $term)) {
                $score += 8;
            }
            if (str_contains($tags, $term)) {
                $score += 7;
            }
            if (str_contains($path, $term)) {
                $score += 6;
            }
            if (str_contains($symbols, $term)) {
                $score += 6;
            }
            if (str_contains($excerpt, $term)) {
                $score += 4;
            }
            if (str_contains($content, $term)) {
                $score += 2;
            }
        }

        if ($type === 'docs') {
            $score += 1;
        }

        return $score;
    }

    private function terms(string $query): array
    {
        $pieces = preg_split('/\s+/', mb_strtolower(trim($query))) ?: [];

        return array_values(array_filter($pieces, static fn (string $term): bool => $term !== ''));
    }
}
