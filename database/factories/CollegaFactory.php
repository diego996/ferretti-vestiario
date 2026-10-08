<?php

namespace Database\Factories;

use App\Models\Collega;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collega>
 */
class CollegaFactory extends Factory
{
    protected $model = Collega::class;

    public function definition(): array
    {
        return [
            'nome' => $this->faker->firstName(),
            'cognome' => $this->faker->lastName(),
            'matricola' => null,
            'attivo' => true,
        ];
    }
}
