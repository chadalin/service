
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Diagnostic\Symptom;
use App\Models\Brand;

class DiagnosticSeeder extends Seeder
{
    public function run(): void
    {
        // Добавляем симптомы Land Rover
        $symptoms = [
            [
                'name' => 'Не заводится',
                'description' => 'Двигатель не запускается при повороте ключа',
                'related_systems' => ['Двигатель', 'Электрика', 'Топливная система'],
                'frequency' => 150,
            ],
            [
                'name' => 'Дёргается при движении',
                'description' => 'Рывки и провалы при разгоне',
                'related_systems' => ['Трансмиссия', 'Двигатель', 'Топливная система'],
                'frequency' => 120,
            ],
            [
                'name' => 'Горит Check Engine',
                'description' => 'Горит индикатор неисправности двигателя',
                'related_systems' => ['Двигатель', 'Электрика'],
                'frequency' => 200,
            ],
            [
                'name' => 'Стук в двигателе',
                'description' => 'Стучащие звуки из двигателя',
                'related_systems' => ['Двигатель'],
                'frequency' => 80,
            ],
            [
                'name' => 'Перегрев двигателя',
                'description' => 'Температура двигателя выше нормы',
                'related_systems' => ['Система охлаждения', 'Двигатель'],
                'frequency' => 60,
            ],
            [
                'name' => 'Проблемы с коробкой передач',
                'description' => 'Рывки при переключении, не включаются передачи',
                'related_systems' => ['Трансмиссия'],
                'frequency' => 90,
            ],
            [
                'name' => 'Не работает кондиционер',
                'description' => 'Кондиционер не охлаждает или не включается',
                'related_systems' => ['Климат-контроль'],
                'frequency' => 70,
            ],
            [
                'name' => 'Плавающие обороты',
                'description' => 'Обороты двигателя нестабильны на холостом ходу',
                'related_systems' => ['Двигатель', 'Электрика'],
                'frequency' => 110,
            ],
        ];
        
        foreach ($symptoms as $symptomData) {
            Symptom::create(array_merge($symptomData, [
                'slug' => \Illuminate\Support\Str::slug($symptomData['name']),
                'is_active' => true,
            ]));
        }
        
        $this->command->info('Добавлены симптомы диагностики');
    }
}