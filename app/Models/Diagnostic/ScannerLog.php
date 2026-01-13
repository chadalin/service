<?php

namespace App\Models\Diagnostic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScannerLog extends Model
{
    protected $table = 'scanner_logs';
    
    protected $fillable = [
        'case_id', 'scanner_type', 'file_name', 'file_path',
        'parsed_data', 'error_codes', 'live_data', 'raw_content'
    ];
    
    protected $casts = [
        'parsed_data' => 'array',
        'error_codes' => 'array',
        'live_data' => 'array',
    ];
    
    public function case(): BelongsTo
    {
        return $this->belongsTo(Case::class);
    }
    
    // Парсинг логов сканера (базовая реализация)
    public function parseLogContent(): array
    {
        $content = file_get_contents(storage_path('app/' . $this->file_path));
        $this->raw_content = $content;
        
        // Базовая логика парсинга (нужно расширить под разные сканеры)
        $parsedData = [
            'error_codes' => $this->extractErrorCodes($content),
            'parameters' => $this->extractParameters($content),
            'timestamp' => now(),
        ];
        
        $this->update([
            'parsed_data' => $parsedData,
            'error_codes' => $parsedData['error_codes'],
        ]);
        
        return $parsedData;
    }
    
    private function extractErrorCodes(string $content): array
    {
        // Регулярные выражения для поиска кодов ошибок
        preg_match_all('/P[0-9A-F]{4}/i', $content, $matches);
        return array_map(fn($code) => ['code' => $code, 'description' => ''], array_unique($matches[0]));
    }
    
    private function extractParameters(string $content): array
    {
        // Базовая логика извлечения параметров
        $parameters = [];
        
        // Пример для Autel
        if (str_contains($content, 'RPM')) {
            preg_match('/RPM:\s*(\d+)/i', $content, $rpmMatch);
            if ($rpmMatch) {
                $parameters['rpm'] = $rpmMatch[1];
            }
        }
        
        return $parameters;
    }
}