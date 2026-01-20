<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Diagnostic\DiagnosticCase;
use App\Models\Diagnostic\Report;
use App\Models\User;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Support\Str;

class DiagnosticCaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        
        if (!$user) {
            $this->command->warn('Пользователь не найден. Создайте сначала пользователя.');
            return;
        }
        
        // Найдем бренд Land Rover по имени
        $brand = Brand::where('name', 'LIKE', '%Land Rover%')->first();
        
        if (!$brand) {
            // Или создадим тестовый бренд
            $brand = Brand::create([
                'name' => 'Land Rover',
                'name_cyrillic' => 'Ленд Ровер',
                'is_popular' => true,
                'country' => 'Великобритания',
            ]);
        }
        
        // Найдем модель
        $model = CarModel::where('brand_id', $brand->id)->first();
        
        if (!$model) {
            // Создадим тестовую модель
            $model = CarModel::create([
                'model_id' => Str::uuid()->toString(),
                'brand_id' => $brand->id,
                'name' => 'Range Rover Sport',
                'name_cyrillic' => 'Рендж Ровер Спорт',
                'class' => 'SUV',
                'year_from' => 2013,
                'year_to' => 2022,
            ]);
        }
        
        // Создаем кейс
        $case = DiagnosticCase::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'brand_id' => $brand->id, // Используем числовой ID
            'model_id' => $model->id,
            'engine_type' => 'Дизель',
            'year' => 2018,
            'mileage' => 125000,
            'symptoms' => [1, 2, 3],
            'description' => 'Проблема с запуском двигателя',
            'status' => 'report_ready',
            'price_estimate' => 4500,
            'time_estimate' => 120,
            'analysis_result' => [
                'possible_causes' => [
                    'Проблема с топливным насосом',
                    'Неисправность свечей накаливания',
                    'Проблемы с аккумулятором',
                ],
                'diagnostic_steps' => [
                    'Проверить давление в топливной системе',
                    'Проверить свечи накаливания',
                    'Проверить заряд аккумулятора',
                ],
                'complexity_level' => 6,
                'estimated_time' => 120,
                'estimated_price' => 4500,
            ],
        ]);
        
        // Создаем отчет
        Report::create([
            'case_id' => $case->id,
            'report_type' => 'premium',
            'summary' => [
                'Выполнена диагностика проблемы с запуском двигателя',
                'Рекомендуется проверка топливной системы',
                'Ориентировочное время ремонта: 3-4 часа',
            ],
            'possible_causes' => [
                [
                    'title' => 'Проблема с топливным насосом',
                    'description' => 'Недостаточное давление в топливной системе',
                    'probability' => 70,
                ],
                [
                    'title' => 'Неисправность свечей накаливания',
                    'description' => 'Свечи не обеспечивают достаточный нагрев',
                    'probability' => 50,
                ],
            ],
            'diagnostic_plan' => [
                [
                    'title' => 'Проверить давление топлива',
                    'description' => 'Использовать манометр для измерения давления',
                    'estimated_time' => 30,
                ],
                [
                    'title' => 'Проверить свечи накаливания',
                    'description' => 'Проверить сопротивление свечей',
                    'estimated_time' => 45,
                ],
            ],
            'estimated_costs' => [
                'diagnostic' => 2000,
                'work' => 8000,
                'total_parts' => 15000,
                'total' => 25000,
                'note' => 'Стоимость может измениться после детальной диагностики',
            ],
            'recommended_actions' => [
                [
                    'title' => 'Замена топливного насоса',
                    'description' => 'Рекомендуется замена при подтверждении диагноза',
                    'priority' => 'high',
                    'deadline' => '2024-12-31',
                ],
            ],
        ]);
        
        $this->command->info('Создан тестовый кейс диагностики с ID: ' . $case->id);
        $this->command->info('Бренд: ' . $brand->name . ' (ID: ' . $brand->id . ')');
        $this->command->info('Модель: ' . $model->name . ' (ID: ' . $model->id . ')');
    }
}