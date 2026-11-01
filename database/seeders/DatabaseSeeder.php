<?php

namespace Database\Seeders;

use App\Domain\Enum\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            DocumentTypeSeeder::class,
            PositionSeeder::class,
            ContractTypeSeeder::class,
            TerminationReasonSeeder::class,
            ConceptCatalogSeeder::class,
            MinimumWageSeeder::class,
            LegalParameterSeeder::class,
        ]);

        foreach ([
            ['Admin Blindem', 'admin@blindem.test', UserRole::Administrator],
            ['Colaborador Blindem', 'colaborador@blindem.test', UserRole::Collaborator],
        ] as [$name, $email, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password')]
            );

            $user->forceFill(['role' => $role->value, 'email_verified_at' => now()])->save();
        }

        $this->call(DemoDataSeeder::class);
    }
}
