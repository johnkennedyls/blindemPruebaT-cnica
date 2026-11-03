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
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@blindem.test')->sole();
        $collaborator = User::query()->where('email', 'colaborador@blindem.test')->sole();

        $andina = $this->company('900412365', 'Comercial Andina de Repuestos S.A.S.', 'Repuestos Andina', '4651', 'Bogotá');
        $neumatica = $this->company('900781204', 'Distribuciones Neumática de Occidente S.A.S.', 'Neumática', '4530', 'Santiago de Cali');
        $meridian = $this->company('901234567', 'Transportes Meridian del Centro S.A.S.', 'Transportes Meridian', '4941', 'Bucaramanga');
        $roble = $this->company('900654321', 'Almacenes El Roble Internacional S.A.S.', 'El Roble', '4711', 'Medellín');
        $margarita = $this->company('901987654', 'Clínica Santa Margarita IPS S.A.S.', 'Santa Margarita', '8610', 'Bogotá');

        $collaborator->companies()->syncWithoutDetaching([
            $andina->id => ['granted_at' => now()],
            $neumatica->id => ['granted_at' => now()],
            $meridian->id => ['granted_at' => now()],
        ]);

        $ana = $this->worker('CC', '1098765432', 'Ana Lucía', 'Martínez Soto', Gender::Femenino, 'ana.martinez@correo.test', '1988-05-14', 'Bogotá', 'Cundinamarca');
        $carlos = $this->worker('CC', '79456123', 'Carlos Andrés', 'Pérez Rojas', Gender::Masculino, 'cperez@correo.test', '1984-08-23', 'Bucaramanga', 'Santander');
        $marta = $this->worker('CE', '52314987', 'Marta Lucía', 'Gómez Ortega', Gender::Femenino, 'magomez@correo.test', '1990-01-31', 'Cúcuta', 'Norte de Santander');
        $jorge = $this->worker('CC', '1023456789', 'Jorge Enrique', 'Roa Castaño', Gender::Masculino, 'jroa@correo.test', '1976-03-09', 'Pereira', 'Risaralda');
        $luisa = $this->worker('CC', '608745123', 'Luisa Fernanda', 'Cardozo Prada', Gender::Femenino, 'lcardozo@correo.test', '1995-11-27', 'Manizales', 'Caldas');
        $diana = $this->worker('CC', '1100987654', 'Diana Marcela', 'Quintero Bailón', Gender::Femenino, 'dquintero@correo.test', '1996-11-03', 'Soacha', 'Cundinamarca');
        $hector = $this->worker('CC', '80554321', 'Héctor Julio', 'Álvarez Nieto', Gender::Masculino, 'halvarez@correo.test', '1979-02-27', 'Medellín', 'Antioquia');
        $sandra = $this->worker('CC', '1015432789', 'Sandra Milena', 'Duarte Cárdenas', Gender::Femenino, 'sduarte@correo.test', '1991-07-19', 'Bucaramanga', 'Santander');
        $oscar = $this->worker('CC', '79988112', 'Óscar Iván', 'Bernal Osorio', Gender::Masculino, 'obernal@correo.test', '1985-09-30', 'Bogotá', 'Cundinamarca');
        $gloria = $this->worker('CC', '1026789345', 'Gloria Esperanza', 'Sáenz Tovar', Gender::Femenino, 'gsaenz@correo.test', '1970-12-15', 'Santiago de Cali', 'Valle del Cauca');
        $yeison = $this->worker('TI', '87654321', 'Yeison Andrés', 'Rincón Torres', Gender::Masculino, 'yrincon@correo.test', '2006-01-08', 'Soacha', 'Cundinamarca');
        $fabian = $this->worker('CC', '52789632', 'Fabián Andrés', 'Camargo Ruiz', Gender::Masculino, 'fcamargo@correo.test', '1983-06-05', 'Bogotá', 'Cundinamarca');
        $natalia = $this->worker('CC', '1032567894', 'Natalia', 'Jiménez Flórez', Gender::Femenino, 'njimenez@correo.test', '1993-03-22', 'Neiva', 'Huila');
        $luz = $this->worker('CC', '1012345678', 'Luz Adriana', 'Cardona Gallo', Gender::Femenino, 'lcardonag@correo.test', '1981-10-11', 'Manizales', 'Caldas');
        $sebastian = $this->worker('CC', '63457891', 'Sebastián', 'Flórez Prieto', Gender::Masculino, 'sflorez@correo.test', '1998-05-30', 'Pereira', 'Risaralda');
        $julio = $this->worker('CE', '79123456', 'Julio María', 'Sandoval Pérez', Gender::Masculino, 'jsandoval@correo.test', '1990-08-17', 'Cúcuta', 'Norte de Santander');

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
            'notes' => 'Contrato vigente: el botón de liquidar sólo aparece cuando pasa a terminado o en liquidación.',
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

        $this->contract('CTR-000006', $collaborator, $andina, $diana, 'INDE', 'AUX-LOG', 'DES-JC', [
            'start_date' => '2024-02-01',
            'end_date' => '2025-06-30',
            'base_salary_monthly' => '1150000.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 8,
            'pending_salary_days' => 12,
            'notes' => 'Salario por debajo del SMLMV y despido con justa causa: formato A sin indemnización.',
        ]);

        $this->contract('CTR-000007', $collaborator, $neumatica, $hector, 'INDE', 'ANL-OP', 'MUT-ACU', [
            'start_date' => '2023-01-10',
            'end_date' => '2025-12-31',
            'base_salary_monthly' => '1423500.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 15,
            'pending_salary_days' => 20,
            'notes' => 'Salario exactamente igual al SMLMV del año de retiro.',
        ]);

        $this->contract('CTR-000008', $collaborator, $meridian, $sandra, 'FIJO', 'VEN-CAM', 'VENC-PLZ', [
            'start_date' => '2024-05-01',
            'end_date' => '2025-04-30',
            'scheduled_end_date' => '2025-04-30',
            'base_salary_monthly' => '2000000.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 10,
            'pending_salary_days' => 15,
            'notes' => 'Término fijo que vence en la fecha pactada: sin indemnización, con auxilio calculado solo.',
        ]);

        $this->contract('CTR-000009', $collaborator, $andina, $oscar, 'INDE', 'CONT', 'DES-SJC', [
            'start_date' => '2022-03-01',
            'end_date' => '2025-09-30',
            'base_salary_monthly' => '4800000.00',
            'transport_auxiliary_monthly' => '0.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::SinDerecho,
            'overtime_average_monthly' => '450000.00',
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 18,
            'pending_salary_days' => 15,
            'notes' => 'Entre dos y diez SMLMV, sin auxilio y con promedio de horas extras en la base prestacional.',
        ]);

        $this->contract('CTR-000010', $admin, $roble, $gloria, 'INDE', 'LID-OP', 'RET-VOL', [
            'start_date' => '2019-01-02',
            'end_date' => '2024-12-31',
            'base_salary_monthly' => '22000000.00',
            'transport_auxiliary_monthly' => '0.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::SinDerecho,
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 20,
            'pending_salary_days' => 10,
            'notes' => 'Más de diez SMLMV: el Excel cambia el rango salarial del tope indemnizatorio. Pertenece a una empresa sin acceso para el colaborador.',
        ]);

        $this->contract('CTR-000011', $collaborator, $neumatica, $yeison, 'APRE', 'ASIG-AD', 'VENC-PLZ', [
            'start_date' => '2024-08-01',
            'end_date' => '2025-07-31',
            'scheduled_end_date' => '2025-07-31',
            'base_salary_monthly' => '1300000.00',
            'transport_auxiliary_monthly' => '0.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::SinDerecho,
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 5,
            'pending_salary_days' => 15,
            'notes' => 'Aprendiz SENA: el tipo de contrato desactiva auxilio de transporte e indemnización.',
        ]);

        $this->contract('CTR-000012', $collaborator, $andina, $fabian, 'INDE', 'AUX-LOG', 'RET-VOL', [
            'start_date' => '2022-02-01',
            'end_date' => '2024-01-31',
            'base_salary_monthly' => '1600000.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 12,
            'pending_salary_days' => 10,
        ]);

        $this->contract('CTR-000013', $collaborator, $meridian, $fabian, 'INDE', 'VEN-CAM', null, [
            'start_date' => '2024-02-01',
            'base_salary_monthly' => '2150000.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::Activo,
            'notes' => 'Segundo vínculo del mismo trabajador en otra empresa, sucesivo al contrato CTR-000012.',
        ]);

        $this->contract('CTR-000014', $collaborator, $andina, $natalia, 'INDE', 'ANL-OP', null, [
            'start_date' => '2025-01-15',
            'base_salary_monthly' => '2400000.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::Activo,
        ]);

        $this->contract('CTR-000015', $collaborator, $neumatica, $natalia, 'INDE', 'ASIG-AD', null, [
            'start_date' => '2025-06-01',
            'base_salary_monthly' => '1900000.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::Activo,
            'notes' => 'Doble vínculo vigente en el mismo rango de fechas: la aplicación lo acepta y cada contrato se liquida aparte.',
        ]);

        $this->contract('CTR-000016', $collaborator, $meridian, $carlos, 'INDE', 'CONT', null, [
            'start_date' => '2025-09-01',
            'base_salary_monthly' => '2900000.00',
            'transport_auxiliary_monthly' => '0.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::SinDerecho,
            'status' => ContractStatus::Activo,
            'notes' => 'El mismo trabajador con un contrato terminado y uno vigente en empresas distintas.',
        ]);

        $this->contract('CTR-000017', $collaborator, $meridian, $marta, 'INDE', 'VEN-CAM', null, [
            'start_date' => '2026-03-01',
            'base_salary_monthly' => '1500000.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::Activo,
        ]);

        $this->contract('CTR-000018', $admin, $margarita, $julio, 'FIJO', 'AUX-LOG', 'DES-SJC', [
            'start_date' => '2024-03-01',
            'end_date' => '2025-10-31',
            'scheduled_end_date' => '2026-02-28',
            'base_salary_monthly' => '1700000.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 9,
            'pending_salary_days' => 15,
            'notes' => 'Término fijo roto antes del vencimiento pactado: la indemnización toma el tiempo que faltaba.',
        ]);

        $this->contract('CTR-000019', $collaborator, $meridian, $luz, 'INDE', 'ANL-OP', 'DES-SJC', [
            'start_date' => '2021-09-01',
            'end_date' => '2026-05-31',
            'base_salary_monthly' => '1850000.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::EnLiquidacion,
            'pending_vacation_days' => 14,
            'pending_salary_days' => 15,
            'notes' => 'Ya en proceso de liquidación: se ve en la cola del panel hasta que se guarda el cálculo.',
        ]);

        $this->contract('CTR-000020', $collaborator, $neumatica, $sebastian, 'INDE', 'AUX-LOG', 'MUT-ACU', [
            'start_date' => '2026-05-01',
            'end_date' => '2026-06-30',
            'base_salary_monthly' => '1600000.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::Auto,
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 1,
            'pending_salary_days' => 20,
            'notes' => 'Vínculo de dos meses: todas las partidas quedan prorrateadas al mínimo.',
        ]);

        $this->contract('CTR-000021', $collaborator, $andina, $luisa, 'OBRA', 'LID-OP', null, [
            'start_date' => '2026-01-05',
            'base_salary_monthly' => '2750000.00',
            'transport_auxiliary_monthly' => '0.00',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::SinDerecho,
            'overtime_average_monthly' => '120000.00',
            'status' => ContractStatus::Activo,
        ]);
    }

    private function company(string $nit, string $businessName, string $tradeName, string $activityCode, string $city): Company
    {
        return Company::updateOrCreate(['nit' => $nit], [
            'business_name' => $businessName,
            'trade_name' => $tradeName,
            'activity_code' => $activityCode,
            'address' => 'Carrera 15 # 45-32',
            'city' => $city,
            'phone' => '6015550142',
            'email' => 'nomina@'.Str::slug($tradeName, '').'.test',
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
        string $birthDate,
        string $city,
        string $department,
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
                'birth_date' => $birthDate,
                'email' => $email,
                'phone' => '3105550142',
                'address' => 'Calle 80 # 12-45',
                'city' => $city,
                'department' => $department,
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
