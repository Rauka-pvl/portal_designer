<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $name = 'Обмер';
        $type = 'measurement';
        $steps = [
            'Длина и высота стен, включая выступы и ниши',
            'Проверка углов (если не 90 градусов, отметить, сколько)',
            'Проверка параллельности стен',
            'Оконные проемы: ширина и высота окон, расстояние от пола до подоконника и от потолка до верхнего края проёма, глубина подоконника.',
            'Дверные проемы: ширина и высота, толщина стен в разрезе',
            'Потолки: высота в нескольких точках каждого помещения, перепады уровней, подиумы, балки',
            'Коммуникации: отметка расположения труб отопления, газопровода, вентиляционных отверстий, стояков водоснабжения и канализации; размеры коммуникаций и привязки к ближайшим стенам; фиксация расположения электрощита, розеток, выключателей, выводов под освещение',
            'Параметры и размеры лестницы',
        ];

        $existing = DB::table('templates')
            ->whereNull('user_id')
            ->where('name', $name)
            ->where('type', $type)
            ->first();

        if (! $existing) {
            DB::table('templates')->insert([
                'user_id' => null,
                'name' => $name,
                'type' => $type,
                'steps' => json_encode($steps, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('templates')
            ->whereNull('user_id')
            ->where('name', 'Обмер')
            ->where('type', 'measurement')
            ->delete();
    }
};

