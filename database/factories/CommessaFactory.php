<?php

namespace Database\Factories;

use App\Models\Commessa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commessa>
 */
class CommessaFactory extends Factory
{
    protected $model = Commessa::class;

    public function definition(): array
    {
        return [
            'codice' => strtoupper($this->faker->unique()->bothify('C-####')),
            'nome' => 'Commessa '.$this->faker->city(),
            'attiva' => true,
        ];
    }
}
