<?php

if (!function_exists('getCaseSymptoms')) {
    function getCaseSymptoms($symptoms) {
        if (empty($symptoms) || !is_array($symptoms)) {
            return [];
        }
        
        $result = [];
        foreach ($symptoms as $symptom) {
            if (is_array($symptom)) {
                if (isset($symptom['name'])) {
                    $result[] = $symptom['name'];
                } elseif (isset($symptom['description'])) {
                    $result[] = $symptom['description'];
                }
            } elseif (is_string($symptom)) {
                $result[] = $symptom;
            }
        }
        
        return array_slice($result, 0, 3); // Возвращаем только первые 3 симптома
    }
}