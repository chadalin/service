<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация поисковой системы для AutoDoc AI
    |
    */
    
    'default' => env('SEARCH_ENGINE', 'mysql'), // mysql, semantic, hybrid
    
    'engines' => [
        'mysql' => [
            'fulltext_enabled' => true,
            'min_word_length' => 3,
            'stopwords' => [
                'и', 'в', 'во', 'не', 'что', 'он', 'на', 'я', 'с', 'со', 'как', 'а',
                'то', 'все', 'она', 'так', 'его', 'но', 'да', 'ты', 'к', 'у', 'же',
                'вы', 'за', 'бы', 'по', 'только', 'ее', 'мне', 'было', 'вот', 'от',
                'меня', 'еще', 'нет', 'о', 'из', 'ему', 'теперь', 'когда', 'даже',
                'ну', 'ли', 'если', 'уже', 'или', 'ни', 'быть', 'был', 'него', 'до',
                'вас', 'нибудь', 'опять', 'уж', 'вам', 'ведь', 'там', 'потом', 'себя',
                'ничего', 'ей', 'может', 'они', 'тут', 'где', 'есть', 'надо', 'ней',
                'для', 'мы', 'тебя', 'их', 'чем', 'была', 'сам', 'чтоб', 'без', 'будто',
                'чего', 'раз', 'тоже', 'себе', 'под', 'будет', 'ж', 'тогда', 'кто',
                'этот', 'того', 'потому', 'этого', 'какой', 'совсем', 'ним', 'здесь',
                'этом', 'один', 'почти', 'мой', 'тем', 'чтобы', 'нее', 'сейчас', 'были',
                'куда', 'зачем', 'всех', 'никогда', 'можно', 'при', 'наконец', 'два',
                'об', 'другой', 'хоть', 'после', 'над', 'больше', 'тот', 'через', 'эти',
                'нас', 'про', 'всего', 'них', 'какая', 'много', 'разве', 'три', 'эту',
                'моя', 'впрочем', 'хорошо', 'свою', 'этой', 'перед', 'иногда', 'лучше',
                'чуть', 'том', 'нельзя', 'такой', 'им', 'более', 'всегда', 'конечно',
                'всю', 'между',
            ],
            'relevance_weights' => [
                'title' => 5.0,
                'keywords' => 3.0,
                'content_text' => 1.0,
                'detected_section' => 4.0,
                'detected_system' => 3.5,
                'detected_component' => 4.5,
            ],
            'fuzzy_search' => true,
            'fuzzy_threshold' => 0.7,
        ],
        
        'semantic' => [
            'enabled' => env('SEMANTIC_SEARCH_ENABLED', false),
            'provider' => 'openai',
            'model' => 'text-embedding-ada-002',
            'dimensions' => 1536,
            'similarity_threshold' => 0.7,
            'cache_embeddings' => true,
            'max_tokens' => 2000,
        ],
        
        'hybrid' => [
            'weights' => [
                'keyword' => 0.4,
                'semantic' => 0.6,
            ],
            'fusion_method' => 'weighted', // weighted, reciprocal_rank, comb_sum
        ],
    ],
    
    'indexing' => [
        'batch_size' => 100,
        'chunk_size' => 1000,
        'max_workers' => 3,
        'auto_index' => true,
        'rebuild_interval' => 86400, // 24 часа в секундах
    ],
    
    'parsing' => [
        'enabled' => true,
        'extract_keywords' => true,
        'detect_categories' => true,
        'generate_embeddings' => env('SEMANTIC_SEARCH_ENABLED', false),
        'min_content_length' => 100,
        'max_content_length' => 100000,
        'languages' => ['ru', 'en'],
    ],
    
    'cache' => [
        'enabled' => true,
        'driver' => env('SEARCH_CACHE_DRIVER', 'database'),
        'ttl' => 3600, // 1 час
        'max_entries' => 10000,
        'popular_queries_ttl' => 86400, // 24 часа
    ],
    
    'analytics' => [
        'track_queries' => true,
        'track_clicks' => true,
        'anonymize_ip' => true,
        'retention_days' => 90,
        'popular_terms_limit' => 100,
    ],
    
    'performance' => [
        'timeout' => 30, // секунды
        'max_results' => 100,
        'suggestions_limit' => 10,
        'debounce_ms' => 300,
    ],
];