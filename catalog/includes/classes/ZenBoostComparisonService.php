<?php

class ZenBoostComparisonService
{
    private ZenBoostSearchService $search;

    public function __construct(?ZenBoostSearchService $search = null)
    {
        $this->search = $search ?? new ZenBoostSearchService();
    }

    public function compare(array $docsIndex, array $repoIndex, string $query, int $limit = 3): array
    {
        $docsResults = $this->search->searchDocs($docsIndex, $query, $limit);
        $repoResults = $this->search->searchRepo($repoIndex, $query, $limit);

        return [
            'query' => $query,
            'docs' => $docsResults,
            'repo' => $repoResults,
            'summary' => $this->buildSummary($docsResults, $repoResults),
            'confidence' => $this->confidence($docsResults, $repoResults),
        ];
    }

    private function buildSummary(array $docsResults, array $repoResults): string
    {
        if ($docsResults === [] && $repoResults === []) {
            return 'No matching docs or code evidence was found.';
        }

        if ($docsResults !== [] && $repoResults !== []) {
            return 'Found both documentation guidance and repository implementation evidence.';
        }

        if ($docsResults !== []) {
            return 'Found documentation guidance, but no matching repository implementation evidence.';
        }

        return 'Found repository implementation evidence, but no matching documentation guidance.';
    }

    private function confidence(array $docsResults, array $repoResults): string
    {
        if ($docsResults !== [] && $repoResults !== []) {
            return 'medium';
        }

        if ($docsResults !== [] || $repoResults !== []) {
            return 'low';
        }

        return 'none';
    }
}
