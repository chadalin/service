<?php
// app/Http/Controllers/Api/SearchController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Основной поиск
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:3|max:255',
            'filters' => 'sometimes|array',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $query = $request->input('query');
        $filters = $request->input('filters', []);
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);

        $results = $this->searchService->paginatedSearch($query, $filters, $page, $perPage);

        return response()->json([
            'success' => true,
            'data' => $results['data'],
            'meta' => [
                'total' => $results['total'],
                'page' => $results['current_page'],
                'per_page' => $results['per_page'],
                'last_page' => $results['last_page']
            ]
        ]);
    }

    /**
     * Интеллектуальный поиск с группировкой
     */
    public function intelligentSearch(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:3|max:255',
            'filters' => 'sometimes|array'
        ]);

        $results = $this->searchService->intelligentSearch(
            $request->input('query'),
            $request->input('filters', [])
        );

        return response()->json([
            'success' => true,
            'data' => $results,
            'query' => $request->input('query'),
            'filters_applied' => $request->input('filters', [])
        ]);
    }

    /**
     * Автодополнение поиска
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100'
        ]);

        $suggestions = Document::autocomplete($request->input('query'));

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Фазированный поиск
     */
    public function fuzzySearch(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:3|max:255',
            'threshold' => 'sometimes|numeric|min:0.1|max:1'
        ]);

        $results = $this->searchService->fuzzySearch(
            $request->input('query'),
            $request->input('threshold', 0.3)
        );

        return response()->json([
            'success' => true,
            'data' => $results,
            'query' => $request->input('query'),
            'threshold' => $request->input('threshold', 0.3)
        ]);
    }

    /**
     * Поиск по конкретному документу
     */
    public function searchWithinDocument(Request $request, $documentId): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255'
        ]);

        $pages = DocumentPage::where('document_id', $documentId)
            ->search($request->input('query'))
            ->get();

        return response()->json([
            'success' => true,
            'document_id' => $documentId,
            'query' => $request->input('query'),
            'matching_pages' => $pages
        ]);
    }

    /**
     * Похожие документы
     */
    public function similarDocuments(Request $request, $documentId): JsonResponse
    {
        $similar = Document::similarDocuments($documentId);

        return response()->json([
            'success' => true,
            'document_id' => $documentId,
            'similar_documents' => $similar
        ]);
    }

    /**
     * Статистика поиска
     */
    public function searchStats(Request $request): JsonResponse
    {
        $stats = [
            'total_documents' => Document::where('is_parsed', true)->count(),
            'total_pages' => DocumentPage::count(),
            'total_words' => Document::where('is_parsed', true)->sum('word_count'),
            'avg_parsing_quality' => Document::where('is_parsed', true)->avg('parsing_quality'),
            'recently_indexed' => Document::where('is_parsed', true)
                ->orderBy('parsed_at', 'desc')
                ->limit(5)
                ->get(['id', 'title', 'parsed_at'])
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}