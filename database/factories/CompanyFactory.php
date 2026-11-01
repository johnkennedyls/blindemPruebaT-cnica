<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'nit' => fake()->unique()->numerify('9########'),
            'business_name' => fake()->company().' S.A.S.',
            'trade_name' => fake()->company(),
            'activity_code' => '4651',
            'address' => fake()->streetAddress(),
            'city' => 'Bogotá',
            'phone' => fake()->numerify('601#####'),
            'email' => fake()->unique()->companyEmail(),
            'legal_rep_name' => fake()->name(),
            'legal_rep_document' => fake()->numerify('##########'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
