<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => 'Apakah listrik sudah termasuk dalam tarif sewa bulanan?',
            'answer' => 'Ya, seluruh tarif sewa di ASRI Boarding House sudah termasuk listrik wajar dan air.',
            'sort_order' => 1,
            'is_published' => true,
        ];
    }
}
