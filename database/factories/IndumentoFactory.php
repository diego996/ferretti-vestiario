<?php

namespace Database\Factories;

use App\Models\CategoriaIndumento;
use App\Models\Indumento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Indumento>
 */
class IndumentoFactory extends Factory
{
    protected $model = Indumento::class;

    public function definition(): array
    {
        return [
            'categoria_id' => CategoriaIndumento::factory(),
            'codice' => strtoupper($this->faker->unique()->bothify('ART-####')),
            'nome' => ucfirst($this->faker->words(3, true)),
            'attivo' => true,
        ];
    }
}
