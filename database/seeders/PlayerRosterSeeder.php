<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;

class PlayerRosterSeeder extends Seeder
{
    public function run(): void
    {
        $players = [
            ['Bartosz Mrozek', 'Bramkarz'],
            ['Plamen Andreev', 'Bramkarz'],
            ['Krzysztof Bąkowski', 'Bramkarz'],
            ['Mateusz Pruchniewski', 'Bramkarz'],
            ['Wojciech Mońka', 'Obrońca'],
            ['Alex Douglas', 'Obrońca'],
            ['Mateusz Skrzypczak', 'Obrońca'],
            ['Antonio Milic', 'Obrońca'],
            ['Bartosz Salamon', 'Obrońca'],
            ['Hubert Janyszka', 'Obrońca'],
            ['Michał Gurgul', 'Obrońca'],
            ['João Moutinho', 'Obrońca'],
            ['Elias Andersson', 'Obrońca'],
            ['Joel Pereira', 'Obrońca'],
            ['Robert Gumny', 'Obrońca'],
            ['Antoni Kozubal', 'Pomocnik'],
            ['Timothy Ouma', 'Pomocnik'],
            ['Gísli Þórðarson', 'Pomocnik'],
            ['Radosław Murawski', 'Pomocnik'],
            ['Sammy Dudek', 'Pomocnik'],
            ['Afonso Sousa', 'Pomocnik'],
            ['Pablo Rodríguez', 'Pomocnik'],
            ['Filip Jagiełło', 'Pomocnik'],
            ['Bartłomiej Barański', 'Pomocnik'],
            ['Luis Palma', 'Pomocnik'],
            ['Leo Bengtsson', 'Pomocnik'],
            ['Daniel Håkans', 'Pomocnik'],
            ['Taofeek Ismaheel', 'Pomocnik'],
            ['Kornel Lisman', 'Pomocnik'],
            ['Tymoteusz Gmur', 'Pomocnik'],
            ['Patrik Wålemark', 'Pomocnik'],
            ['Ali Gholizadeh', 'Pomocnik'],
            ['Yannick Agnero', 'Napastnik'],
            ['Filip Szymczak', 'Napastnik'],
            ['Mikael Ishak', 'Napastnik'],
            ['Bryan Fiabema', 'Napastnik'],
            ['Kamil Jakóbczyk', 'Napastnik'],
            ['Wojciech Szymczak', 'Napastnik'],
            ['Adrian Kostrzewski', 'Bramkarz'],
            ['Aleks Olsztyn', 'Bramkarz'],
            ['Jakub Skowroński', 'Obrońca'],
            ['Jakub Falkiewicz', 'Obrońca'],
            ['Patryk Kowalski', 'Obrońca'],
            ['Hubert Mich', 'Obrońca'],
            ['Xavier Koral', 'Obrońca'],
            ['Aleksander Klimkiewicz', 'Obrońca'],
            ['Filip Tokar', 'Obrońca'],
            ['Piotr Bartczak', 'Obrońca'],
            ['Serafim Bakhno', 'Pomocnik'],
            ['Igor Stankiewicz', 'Pomocnik'],
            ['Radosław Gołębiewski', 'Pomocnik'],
            ['Igor Draszczyk', 'Pomocnik'],
            ['Alan Majewski', 'Pomocnik'],
            ['Daniel Chejdysz', 'Pomocnik'],
            ['Miłosz Noski', 'Pomocnik'],
            ['Eryk Śledziński', 'Pomocnik'],
            ['Mateusz Cegiełka', 'Pomocnik'],
            ['Maksymilian Dziuba', 'Pomocnik'],
            ['Filip Tonder', 'Napastnik'],
            ['Patryk Palat', 'Napastnik'],
            ['Igor Owczarek', 'Napastnik'],
        ];

        collect($players)->values()->each(function (array $player, int $index): void {
            Player::query()->updateOrCreate(
                ['name' => $player[0]],
                ['shirt_number' => $index + 1, 'position' => $player[1], 'is_active' => true],
            );
        });
    }
}
