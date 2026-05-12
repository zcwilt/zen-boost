<?php

class ZenBoostAnswerService
{
    private ZenBoostComparisonService $comparison;

    public function __construct(?ZenBoostComparisonService $comparison = null)
    {
        $this->comparison = $comparison ?? new ZenBoostComparisonService();
    }

    public function answer(array $docsIndex, array $repoIndex, string $question, int $limit = 3): array
    {
        $comparison = $this->comparison->compare($docsIndex, $repoIndex, $question, $limit);

        return [
            'question' => $question,
            'documented_approach' => $this->summarizeDocs($comparison['docs'] ?? []),
            'current_repo_behavior' => $this->summarizeRepo($comparison['repo'] ?? []),
            'mismatch_note' => $this->buildMismatchNote($comparison),
            'confidence' => (string)($comparison['confidence'] ?? 'none'),
            'docs' => $comparison['docs'] ?? [],
            'repo' => $comparison['repo'] ?? [],
        ];
    }

    private function summarizeDocs(array $records): string
    {
        if ($records === []) {
            return 'No matching official documentation evidence was found in the local Zen Boost cache.';
        }

        $record = $records[0];
        $title = (string)($record['title'] ?? 'Untitled docs record');
        $heading = $this->headingText($record);
        $excerpt = $this->shortText((string)($record['excerpt'] ?? ($record['content'] ?? '')));
        $url = (string)($record['url'] ?? '');

        $parts = ['Top docs match: ' . $title . ($heading === '' ? '' : ' (' . $heading . ')') . '.'];
        if ($excerpt !== '') {
            $parts[] = $excerpt;
        }
        if ($url !== '') {
            $parts[] = 'Source: ' . $url;
        }

        return implode(' ', $parts);
    }

    private function summarizeRepo(array $records): string
    {
        if ($records === []) {
            return 'No matching repository evidence was found in the local Zen Cart catalog.';
        }

        $record = $records[0];
        $path = (string)($record['path'] ?? 'unknown path');
        $title = (string)($record['title'] ?? basename($path));
        $excerpt = $this->shortText((string)($record['excerpt'] ?? ($record['content'] ?? '')));

        $parts = ['Top repo match: ' . $title . ' at ' . $path . '.'];
        if ($excerpt !== '') {
            $parts[] = $excerpt;
        }

        return implode(' ', $parts);
    }

    private function buildMismatchNote(array $comparison): string
    {
        $docs = $comparison['docs'] ?? [];
        $repo = $comparison['repo'] ?? [];

        if ($docs !== [] && $repo !== []) {
            return 'Both docs and code evidence were found. Prefer docs for intended conventions and code for the current runtime behavior.';
        }

        if ($docs !== []) {
            return 'Documentation evidence exists without a matching repo hit. The implementation may live outside the indexed paths or may not exist yet.';
        }

        if ($repo !== []) {
            return 'Repository evidence exists without a matching docs hit. Treat the current code as runtime truth and verify whether the docs are incomplete or outdated.';
        }

        return 'No docs or code evidence was found for this question in the current local catalogs.';
    }

    private function headingText(array $record): string
    {
        $headingPath = $record['heading_path'] ?? [];
        if (!is_array($headingPath) || $headingPath === []) {
            return '';
        }

        return implode(' > ', array_map('strval', $headingPath));
    }

    private function shortText(string $text, int $limit = 220): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 3)) . '...';
    }
}
