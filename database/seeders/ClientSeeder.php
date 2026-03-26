<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            // На всякий случай: если вдруг UserSeeder не успел отработать
            return;
        }

        $faker = FakerFactory::create();

        $statuses = ['new', 'in_work', 'not_working'];
        $total = 50;

        for ($i = 1; $i <= $total; $i++) {
            $email = "client{$i}@example.com";
            $clientType = $faker->randomElement(['person', 'company']);

            // Генерим телефон в формате +7 (XXX) XXX-XX-XX
            $a = str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
            $b = str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
            $c = str_pad((string) random_int(10, 99), 2, '0', STR_PAD_LEFT);

            $phoneSuffix = $faker->numberBetween(0, 99);

            $client = Client::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'email' => $email,
                ],
                [
                    'client_type' => $clientType,
                    'full_name' => $clientType === 'company'
                        ? $faker->company
                        : ($faker->name() . ' ' . $faker->lastName()),
                    'phone' => '+7 (' . $a . ') ' . $b . '-' . $c . '-' . $phoneSuffix,
                    'email' => $email,
                    'status' => $statuses[array_rand($statuses)],
                    'comment' => $faker->sentence(4),
                    'link' => $faker->url(),
                ]
            );
        }
    }
}

