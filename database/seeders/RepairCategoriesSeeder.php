<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RepairCategory;

class RepairCategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Двигатель', 'description' => 'Ремонт и обслуживание двигателя'],
            ['name' => 'Трансмиссия', 'description' => 'Коробка передач, сцепление, приводы'],
            ['name' => 'Ходовая часть', 'description' => 'Подвеска, амортизаторы, рычаги'],
            ['name' => 'Тормозная система', 'description' => 'Тормозные колодки, диски, суппорты'],
            ['name' => 'Электрика', 'description' => 'Электрооборудование, проводка, аккумулятор'],
            ['name' => 'Кузов', 'description' => 'Кузовные работы, покраска, рихтовка'],
            ['name' => 'Система охлаждения', 'description' => 'Радиатор, помпа, термостат'],
            ['name' => 'Выхлопная система', 'description' => 'Глушитель, катализатор, выхлопная труба'],
            ['name' => 'Рулевое управление', 'description' => 'Рулевая рейка, наконечники, ГУР'],
            ['name' => 'Кондиционер', 'description' => 'Система кондиционирования и вентиляции'],
        ];

        foreach ($categories as $category) {
            RepairCategory::create($category);
        }
    }
}