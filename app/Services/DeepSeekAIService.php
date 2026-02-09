<?php
namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DeepSeekAIService
{
    private $client;
    private $apiKey;
    private $baseUrl = 'https://api.deepseek.com/v1';
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        $this->apiKey = config('services.deepseek.api_key');
    }
    
    /**
     * Анализ диагностической проблемы с контекстом из БД
     */
    public function analyzeDiagnosticProblem(string $query, array $symptoms, array $parts = [], array $docs = [], ?int $brandId = null, ?int $modelId = null): array
    {
        $cacheKey = 'deepseek_diagnosis_' . md5($query . $brandId . $modelId . json_encode($symptoms));
        
        // Кэшируем на 1 час для одинаковых запросов
        return Cache::remember($cacheKey, 3600, function () use ($query, $symptoms, $parts, $docs, $brandId, $modelId) {
            try {
                $context = $this->buildDiagnosticContext($symptoms, $parts, $docs, $brandId, $modelId);
                $prompt = $this->buildDiagnosticPrompt($query, $context);
                
                $response = $this->client->post($this->baseUrl . '/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'deepseek-chat',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $this->getSystemPrompt()
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'temperature' => 0.3,
                        'max_tokens' => 1500,
                        'response_format' => [
                            'type' => 'json_object'
                        ]
                    ]
                ]);
                
                $data = json_decode($response->getBody(), true);
                
                return $this->parseAIResponse($data['choices'][0]['message']['content'] ?? '');
                
            } catch (\Exception $e) {
                Log::error('DeepSeek API Error: ' . $e->getMessage());
                return $this->getFallbackResponse();
            }
        });
    }
    
    /**
     * Дополнительная диагностика для конкретного симптома
     */
    public function enhanceSymptomAnalysis(array $symptom, array $relatedSymptoms = []): array
    {
        $prompt = $this->buildSymptomEnhancementPrompt($symptom, $relatedSymptoms);
        
        try {
            $response = $this->client->post($this->baseUrl . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Ты опытный автомеханик-диагност с 20-летним стажем. Давай детальные, практические рекомендации.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 1000
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            return [
                'enhanced_analysis' => $data['choices'][0]['message']['content'] ?? '',
                'confidence_score' => $this->calculateConfidenceScore($symptom)
            ];
            
        } catch (\Exception $e) {
            Log::error('DeepSeek enhancement error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Поиск дополнительных причин и решений
     */
    public function findAdditionalCauses(array $symptomData, string $userQuery): array
    {
        $cacheKey = 'deepseek_causes_' . md5(json_encode($symptomData) . $userQuery);
        
        return Cache::remember($cacheKey, 1800, function () use ($symptomData, $userQuery) {
            $prompt = "На основе следующих данных о симптоме и запросе пользователя, предложи дополнительные возможные причины и решения:\n\n"
                . "Запрос пользователя: {$userQuery}\n"
                . "Основной симптом: " . ($symptomData['title'] ?? '') . "\n"
                . "Описание: " . ($symptomData['description'] ?? '') . "\n"
                . "Существующие причины: " . implode(', ', $symptomData['possible_causes'] ?? []) . "\n\n"
                . "Предложи 3-5 дополнительных возможных причин с кратким описанием и рекомендациями по проверке.";
            
            try {
                $response = $this->client->post($this->baseUrl . '/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'deepseek-chat',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Ты автомеханик-эксперт. Дай дополнительные, неочевидные причины проблем.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'temperature' => 0.4,
                        'max_tokens' => 800
                    ]
                ]);
                
                $data = json_decode($response->getBody(), true);
                return $this->parseCausesResponse($data['choices'][0]['message']['content'] ?? '');
                
            } catch (\Exception $e) {
                Log::error('DeepSeek causes error: ' . $e->getMessage());
                return [];
            }
        });
    }
    
    /**
     * Построение контекста для AI
     */
    private function buildDiagnosticContext(array $symptoms, array $parts, array $docs, ?int $brandId, ?int $modelId): string
    {
        $context = "Данные из базы знаний автосервиса:\n\n";
        
        // Симптомы
        if (!empty($symptoms)) {
            $context .= "=== НАЙДЕННЫЕ СИМПТОМЫ ===\n";
            foreach (array_slice($symptoms, 0, 5) as $index => $symptom) {
                $context .= ($index + 1) . ". " . ($symptom['title'] ?? '') . "\n";
                if (!empty($symptom['description'])) {
                    $context .= "   Описание: " . $symptom['description'] . "\n";
                }
                if (!empty($symptom['possible_causes'])) {
                    $context .= "   Возможные причины: " . implode(', ', array_slice($symptom['possible_causes'], 0, 3)) . "\n";
                }
                if (!empty($symptom['brand'])) {
                    $context .= "   Марка/модель: " . $symptom['brand'] . " " . ($symptom['model'] ?? '') . "\n";
                }
                $context .= "\n";
            }
        }
        
        // Запчасти
        if (!empty($parts)) {
            $context .= "=== РЕКОМЕНДУЕМЫЕ ЗАПЧАСТИ ===\n";
            foreach (array_slice($parts, 0, 3) as $part) {
                $context .= "• " . ($part['name'] ?? '') . " (" . ($part['brand'] ?? '') . ") - " . ($part['formatted_price'] ?? '0') . " руб.\n";
            }
            $context .= "\n";
        }
        
        // Документы
        if (!empty($docs)) {
            $context .= "=== ДОКУМЕНТАЦИЯ ===\n";
            foreach (array_slice($docs, 0, 3) as $doc) {
                $context .= "• " . ($doc['title'] ?? 'Документ') . " - " . ($doc['detected_system'] ?? '') . "\n";
            }
        }
        
        return $context;
    }
    
    /**
     * Построение промпта для диагностики
     */
    private function buildDiagnosticPrompt(string $query, string $context): string
    {
        return <<<PROMPT
Пользователь описывает проблему с автомобилем:
"$query"

Контекст из базы данных автосервиса:
$context

Проанализируй проблему и предоставь структурированный ответ в формате JSON со следующими полями:
{
  "summary": "Краткое резюме проблемы",
  "confidence_score": "Оценка уверенности в диагнозе (0-100)",
  "most_likely_cause": "Наиболее вероятная причина",
  "additional_causes": ["Дополнительные возможные причины"],
  "diagnostic_steps": ["Пошаговый план диагностики"],
  "repair_recommendations": ["Рекомендации по ремонту"],
  "estimated_time": "Примерное время ремонта",
  "estimated_cost": "Примерная стоимость ремонта",
  "safety_warnings": ["Меры предосторожности"],
  "tools_required": ["Необходимые инструменты"],
  "immediate_actions": ["Необходимые действия немедленно"],
  "when_to_contact_professional": "Когда обращаться к специалисту"
}

Ответ должен быть на русском языке, технически точным и практичным.
PROMPT;
    }
    
    /**
     * Системный промпт
     */
    private function getSystemPrompt(): string
    {
        return <<<SYSTEM
Ты опытный автомеханик-диагност с 20-летним стажем работы в автосервисе. 
Твоя задача - анализировать симптомы проблем с автомобилями и давать точные, практические рекомендации.

Особенности твоих ответов:
1. Технически точные и конкретные
2. Практические и применимые в реальных условиях
3. Учитывай стоимость и доступность запчастей
4. Указывай меры безопасности
5. Давай пошаговые инструкции
6. Оценивай сложность работ
7. Предлагай альтернативные варианты

Всегда указывай, когда нужно обратиться к специалисту.
Отвечай на русском языке без жаргонизмов, но с использованием правильных технических терминов.
SYSTEM;
    }
    
    /**
     * Парсинг ответа AI
     */
    private function parseAIResponse(string $response): array
    {
        try {
            $data = json_decode($response, true);
            
            // Проверяем корректность JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Пробуем извлечь JSON из текста
                preg_match('/\{.*\}/s', $response, $matches);
                if (!empty($matches)) {
                    $data = json_decode($matches[0], true);
                }
            }
            
            if (!$data || !is_array($data)) {
                return $this->parseTextResponse($response);
            }
            
            return array_merge($this->getDefaultResponse(), $data);
            
        } catch (\Exception $e) {
            Log::error('Error parsing AI response: ' . $e->getMessage());
            return $this->getDefaultResponse();
        }
    }
    
    /**
     * Парсинг текстового ответа
     */
    private function parseTextResponse(string $response): array
    {
        $result = $this->getDefaultResponse();
        $result['summary'] = substr($response, 0, 300);
        
        // Пытаемся извлечь структурированные данные
        if (preg_match('/Диагноз:?(.*?)(?=\n|$)/i', $response, $matches)) {
            $result['most_likely_cause'] = trim($matches[1]);
        }
        
        return $result;
    }
    
    /**
     * Ответ по умолчанию
     */
    private function getDefaultResponse(): array
    {
        return [
            'summary' => 'Требуется дополнительная диагностика.',
            'confidence_score' => 50,
            'most_likely_cause' => 'Требуется осмотр специалиста',
            'additional_causes' => [],
            'diagnostic_steps' => ['Провести визуальный осмотр', 'Считать коды ошибок'],
            'repair_recommendations' => ['Обратиться в сервис для точной диагностики'],
            'estimated_time' => '1-2 часа',
            'estimated_cost' => 'от 2000 руб.',
            'safety_warnings' => ['Работать только при выключенном двигателе'],
            'tools_required' => ['Мультиметр', 'Диагностический сканер'],
            'immediate_actions' => ['Проверить уровень масла и охлаждающей жидкости'],
            'when_to_contact_professional' => 'При отсутствии опыта ремонтных работ'
        ];
    }
    
    /**
     * Ответ при ошибке
     */
    private function getFallbackResponse(): array
    {
        return [
            'summary' => 'Проблема требует профессиональной диагностики. Рекомендуем обратиться в сервис.',
            'confidence_score' => 0,
            'most_likely_cause' => 'Требуется диагностика специалиста',
            'additional_causes' => [],
            'diagnostic_steps' => ['Обратиться в сервис для полной диагностики'],
            'repair_recommendations' => ['Записаться на диагностику в автосервис'],
            'estimated_time' => '2-3 часа',
            'estimated_cost' => 'от 3000 руб.',
            'safety_warnings' => ['Не пытайтесь ремонтировать без опыта'],
            'tools_required' => ['Профессиональное диагностическое оборудование'],
            'immediate_actions' => ['Проверить основные жидкости и давление в шинах'],
            'when_to_contact_professional' => 'Немедленно'
        ];
    }
    
    private function buildSymptomEnhancementPrompt(array $symptom, array $relatedSymptoms): string
    {
        // Реализация построения промпта для улучшения анализа симптома
        return "Детализируй анализ симптома...";
    }
    
    private function calculateConfidenceScore(array $symptom): float
    {
        // Реализация расчета уверенности
        return 0.7;
    }
    
    private function parseCausesResponse(string $response): array
    {
        // Реализация парсинга ответа о причинах
        return [];
    }
}