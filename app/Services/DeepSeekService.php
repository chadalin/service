<?php
namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl = 'https://api.deepseek.com/v1';

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('DEEPSEEK_API_KEY');
    }

    public function analyzeDiagnostic($errorCode, $symptoms, $contextFromDB)
    {
        $prompt = $this->buildPrompt($errorCode, $symptoms, $contextFromDB);
        
        try {
            $response = $this->client->post($this->baseUrl . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Ты опытный автомеханик. Анализируй ошибки и симптомы, давай точные инструкции по диагностике и ремонту.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 2000
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('DeepSeek API error: ' . $e->getMessage());
            return null;
        }
    }

    private function buildPrompt($errorCode, $symptoms, $context)
    {
        return "
        Техническая информация из базы данных:
        {$context}
        
        Запрос пользователя:
        Код ошибки: {$errorCode}
        Симптомы: {$symptoms}
        
        Проанализируй и предоставь:
        1. Вероятные причины
        2. Пошаговый план диагностики
        3. Рекомендации по ремонту
        4. Специфичные инструменты
        5. Меры предосторожности
        ";
    }
}