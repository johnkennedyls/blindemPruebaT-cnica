<?php

namespace Database\Seeders;

use App\Domain\Enum\ContractStatus;
use App\Domain\Enum\Gender;
use App\Domain\Enum\TransportAuxiliaryMode;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\DocumentType;
use App\Models\Position;
use App\Models\TerminationReason;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $collaborator = User::query()->where('email', 'colaborador@blindem.test')->sole();

        $andina = $this->company('900412365', 'Comercial Andina de Repuestos S.A.S.', 'Repuestos Andina');
        $neumatica = $this->company('900781204', 'Distribuciones Neumática de Occidente S.A.S.', 'Neumática');

        $collaborator->companies()->syncWithoutDetaching([$andina->id => ['granted_at' => now()]]);

        $ana = $this->worker('CC', '1098765432', 'Ana Lucía', 'Martínez Soto', Gender::Femenino, 'ana.martinez@correo.test');
        $carlos = $this->worker('CC', '79456123', 'Carlos Andrés', 'Pérez Rojas', Gender::Masculino, 'cperez@correo.test');
        $marta = $this->worker('CE', '52314987', 'Marta Lucía', 'Gómez Ortega', Gender::Femenino, 'magomez@correo.test');
        $jorge = $this->worker('CC', '1023456789', 'Jorge Enrique', 'Roa Castaño', Gender::Masculino, 'jroa@correo.test');
        $luisa = $this->worker('CC', '608745123', 'Luisa Fernanda', 'Cardozo Prada', Gender::Femenino, 'lcardozo@correo.test');

        $this->contract('CTR-000001', $collaborator, $andina, $ana, 'INDE', 'ANL-OP', 'DES-SJC', [
            'start_date' => '2021-03-15',
            'end_date' => '2024-06-30',
            'base_salary_monthly' => '1750905.00',
            'transport_auxiliary_monthly' => '249095.00',
            'overtime_average_monthly' => '150000.00',
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 15,
            'pending_salary_days' => 15,
            'notes' => 'Liquidación de ejemplo tomada del Excel de la calculadora del cliente.',
        ]);

        $this->contract('CTR-000002', $collaborator, $andina, $carlos, 'INDE', 'CONT', 'RET-VOL', [
            'start_date' => '2023-02-01',
            'end_date' => '2025-08-31',
            'base_salary_monthly' => '2600000.00',
            'transport_auxiliary_monthly' => '0.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::SinDerecho,
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 12,
            'pending_salary_days' => 15,
        ]);

        $this->contract('CTR-000003', $collaborator, $neumatica, $marta, 'FIJO', 'VEN-CAM', 'VENC-PLZ', [
            'start_date' => '2024-03-01',
            'end_date' => '2026-02-28',
            'scheduled_end_date' => '2026-02-28',
            'base_salary_monthly' => '1300000.00',
            'transport_auxiliary_monthly' => '132800.00',
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 0,
            'pending_salary_days' => 15,
        ]);

        $this->contract('CTR-000004', $collaborator, $andina, $jorge, 'INDE', 'LID-OP', null, [
            'start_date' => '2025-06-01',
            'end_date' => null,
            'base_salary_monthly' => '3200000.00',
            'transport_auxiliary_monthly' => '0.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::SinDerecho,
            'status' => ContractStatus::Activo,
        ]);

        $this->contract('CTR-000005', $collaborator, $neumatica, $luisa, 'OBRA', 'AUX-LOG', 'MUT-ACU', [
            'start_date' => '2024-01-10',
            'end_date' => '2025-11-30',
            'base_salary_monthly' => '1850000.00',
            'transport_auxiliary_monthly' => '153100.00',
            'overtime_average_monthly' => '80000.00',
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 20,
            'pending_salary_days' => 20,
        ]);
    }

    private function company(string $nit, string $businessName, string $tradeName): Company
    {
        return Company::updateOrCreate(['nit' => $nit], [
            'business_name' => $businessName,
            'trade_name' => $tradeName,
            'activity_code' => '4651',
            'address' => 'Carrera 15 # 45-32',
            'city' => 'Bogotá',
            'phone' => '6015550142',
            'email' => 'nomina@'.$tradeName.'.test',
            'legal_rep_name' => 'Ricardo Antonio Mejía',
            'legal_rep_document' => '17123456',
            'is_active' => true,
        ]);
    }

    private function worker(
        string $documentTypeCode,
        string $documentNumber,
        string $firstNames,
        string $lastNames,
        Gender $gender,
        string $email,
    ): Worker {
        return Worker::updateOrCreate(
            [
                'document_type_id' => DocumentType::query()->where('code', $documentTypeCode)->sole()->id,
                'document_number' => $documentNumber,
            ],
            [
                'first_names' => $firstNames,
                'last_names' => $lastNames,
                'gender' => $gender,
                'birth_date' => '1988-05-14',
                'email' => $email,
                'phone' => '3105550142',
                'address' => 'Calle 80 # 12-45',
                'city' => 'Bogotá',
                'department' => 'Cundinamarca',
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function contract(
        string $code,
        User $creator,
        Company $company,
        Worker $worker,
        string $typeCode,
        string $positionCode,
        ?string $reasonCode,
        array $attributes,
    ): Contract {
        return Contract::updateOrCreate(
            ['code' => $code],
            [
                'worker_id' => $worker->id,
                'company_id' => $company->id,
                'contract_type_id' => ContractType::query()->where('code', $typeCode)->sole()->id,
                'position_id' => Position::query()->where('code', $positionCode)->sole()->id,
                'termination_reason_id' => $reasonCode === null
                    ? null
                    : TerminationReason::query()->where('code', $reasonCode)->sole()->id,
                'created_by' => $creator->id,
                'salary_effective_from' => $attributes['start_date'],
                'transport_auxiliary_mode' => TransportAuxiliaryMode::Manual,
                'overtime_average_monthly' => '0.00',
                'pending_vacation_days' => 0,
                'pending_salary_days' => 0,
                'status' => ContractStatus::Activo,
                ...$attributes,
            ],
        );
    }
}
