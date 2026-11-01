<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Position;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contract> */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'code' => 'CTR-'.fake()->unique()->numerify('######'),
            'worker_id' => Worker::factory(),
            'company_id' => Company::factory(),
            'contract_type_id' => ContractType::query()->orderBy('id')->value('id'),
            'position_id' => Position::query()->orderBy('id')->value('id'),
            'created_by' => User::factory(),
            'start_date' => '2026-01-15',
            'end_date' => null,
            'base_salary_monthly' => '2500000.00',
            'transport_auxiliary_monthly' => '249095.00',
        ];
    }
}
