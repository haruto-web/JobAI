<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebSearchService
{
    protected $apiKey;
    protected $searchEngineId;

    public function __construct()
    {
        $this->apiKey = config('services.google_search.api_key');
        $this->searchEngineId = config('services.google_search.search_engine_id');

        if (!$this->apiKey || !$this->searchEngineId) {
            throw new \RuntimeException('Google Search API key or Search Engine ID not configured');
        }
    }

    /**
     * Search the web using Google Custom Search API
     *
     * @param string $query
     * @param int $numResults
     * @return array
     */
    public function search($query, $numResults = 5)
    {
        try {
            $response = Http::get('https://www.googleapis.com/customsearch/v1', [
                'key' => $this->apiKey,
                'cx' => $this->searchEngineId,
                'q' => $query,
                'num' => min($numResults, 10), // Google allows max 10 results per request
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $results = [];
                if (isset($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $results[] = [
                            'title' => $item['title'] ?? '',
                            'link' => $item['link'] ?? '',
                            'snippet' => $item['snippet'] ?? '',
                            'displayLink' => $item['displayLink'] ?? '',
                        ];
                    }
                }

                return $results;
            } else {
                Log::error('Google Search API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Web search failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Check if a query requires web search
     *
     * @param string $query
     * @return bool
     */
    public function requiresWebSearch($query)
    {
        $queryLower = strtolower($query);

        // Keywords that indicate web search is needed
        $searchIndicators = [
            'what is',
            'who is',
            'how to',
            'latest',
            'current',
            'news about',
            'information on',
            'tell me about',
            'explain',
            'search for',
            'find',
            'look up',
            'research',
            'what are the',
            'where is',
            'when is',
            'why is',
            'how does',
            'what does',
            'what happened',
            'recent',
            'update on',
            'facts about',
            'statistics on',
            'data on',
            'trends in',
            'market for',
            'industry',
            'company',
            'technology',
            'science',
            'health',
            'finance',
            'politics',
            'sports',
            'entertainment',
            'weather',
            'events',
            'prices',
            'costs',
            'rates',
            'reviews',
            'best',
            'top',
            'ranking',
            'comparison',
            'vs',
            'versus',
            'difference between',
            'salary',
            'pay',
            'compensation',
            'wage',
            'income',
            'earnings',
            'highest paying',
            'high salary',
            'well paid',
            'lucrative',
        ];

        foreach ($searchIndicators as $indicator) {
            if (str_contains($queryLower, $indicator)) {
                return true;
            }
        }

        return false;
    }
}
