<?php

namespace Database\Factories;

use App\Models\Indumento;
use App\Models\IndumentoVariante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * La giacenza parte sempre da zero: per avere disponibilità usare MagazzinoService.
 *
 * @extends Factory<IndumentoVariante>
 */
class IndumentoVarianteFactory extends Factory
{
    protected $model = IndumentoVariante::class;

    public function definition(): array
    {
        return [
            'indumento_id' => Indumento::factory(),
            'taglia' => $this->faker->unique()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '40', '41', '42', '43', '44', '45', '46']),
            'ordinamento' => 0,
            'scorta_minima' => 5,
            'attiva' => true,
        ];
    }
}
