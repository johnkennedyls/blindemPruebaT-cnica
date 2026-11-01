<?php

namespace Database\Factories;

use App\Domain\Enum\Gender;
use App\Models\DocumentType;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Worker> */
class WorkerFactory extends Factory
{
    protected $model = Worker::class;

    public function definition(): array
    {
        return [
            'document_type_id' => DocumentType::query()->orderBy('id')->value('id'),
            'document_number' => fake()->unique()->numerify('##########'),
            'first_names' => fake()->firstName(),
            'last_names' => fake()->lastName(),
            'gender' => fake()->randomElement(array_column(Gender::cases(), 'value')),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-22 years')->format('Y-m-d'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('3#########'),
            'city' => 'Bogotá',
            'department' => 'Cundinamarca',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
