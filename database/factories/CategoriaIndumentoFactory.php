<?php

namespace Database\Factories;

use App\Models\CategoriaIndumento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaIndumento>
 */
class CategoriaIndumentoFactory extends Factory
{
    protected $model = CategoriaIndumento::class;

    public function definition(): array
    {
        return [
            'nome' => ucfirst($this->faker->unique()->words(2, true)),
            'ordinamento' => 0,
            'attiva' => true,
        ];
    }
}
