<?php

if (!function_exists('formatBytes')) {
    /**
     * Форматирует размер в байтах в читаемый вид
     *
     * @param int $bytes Размер в байтах
     * @param int $decimals Количество знаков после запятой
     * @return string
     */
    function formatBytes($bytes, $decimals = 2)
    {
        if ($bytes <= 0) {
            return '0 Bytes';
        }
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        
        return number_format($bytes / pow($k, $i), $decimals) . ' ' . $sizes[$i];
    }
}

if (!function_exists('filesizeSafe')) {
    /**
     * Безопасное получение размера файла
     *
     * @param string $path Путь к файлу
     * @return int
     */
    function filesizeSafe($path)
    {
        try {
            return file_exists($path) ? filesize($path) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}