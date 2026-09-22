<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BonusQuestionPool;
use App\Models\Competition;
use Illuminate\Database\Seeder;

class LechTyperSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Liga', 'liga'],
            ['Puchar Polski', 'puchar-polski'],
            ['Liga Mistrzów', 'liga-mistrzow'],
            ['Liga Europy', 'liga-europy'],
            ['Liga Konferencji', 'liga-konferencji'],
            ['Golden League', 'golden-league'],
        ] as [$name, $slug]) {
            Competition::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name],
            );
        }

        foreach ([
            'Czy Lech strzeli co najmniej jednego gola?',
            'Czy Lech odda więcej strzałów celnych niż rywal?',
            'Czy Lech wygra pierwszą połowę?',
            'Czy Lech wykona więcej rzutów rożnych niż rywal?',
            'Czy Lech zdobędzie gola po przerwie?',
            'Czy Lech strzeli gola w pierwszych 30 minutach?',
            'Czy Lech zachowa czyste konto?',
            'Czy rywal nie strzeli gola w pierwszej połowie?',
            'Czy Lech wygra więcej pojedynków defensywnych?',
            'Czy rywal odda mniej niż 5 celnych strzałów?',
            'Czy Lech nie straci gola po stałym fragmencie?',
            'Czy Lech nie przegra meczu?',
        ] as $questionText) {
            BonusQuestionPool::query()->firstOrCreate([
                'question_text' => $questionText,
            ]);
        }
    }
}
