<?php

namespace App\Http\Parametrics;

use App\Domain\Enum\ContractModality;
use App\Domain\Enum\HolderType;
use App\Domain\Enum\IndemnizationRule;
use App\Domain\Enum\TerminationClassification;
use App\Models\ContractType;
use App\Models\DocumentType;
use App\Models\MinimumWage;
use App\Models\Position;
use App\Models\TerminationReason;

final class ParametricRegistry
{
    /**
     * @return array<int, ParametricDefinition>
     */
    public static function all(): array
    {
        return [
            self::documentTypes(),
            self::positions(),
            self::contractTypes(),
            self::terminationReasons(),
            self::minimumWages(),
        ];
    }

    public static function resolve(string $slug): ParametricDefinition
    {
        foreach (self::all() as $definition) {
            if ($definition->slug === $slug) {
                return $definition;
            }
        }

        abort(404);
    }

    private static function documentTypes(): ParametricDefinition
    {
        return new ParametricDefinition(
            slug: 'tipos-de-documento',
            model: DocumentType::class,
            table: 'document_types',
            title: 'Tipos de documento',
            one: 'tipo de documento',
            description: 'Catálogo que identifica a trabajadores y representantes legales en filtros y liquidaciones.',
            fields: [
                new ParametricField(name: 'code', label: 'Código', kind: FieldType::Text, required: true, maxLength: 20, hint: 'Ejemplo: CC, CE, TI, NIT.'),
                new ParametricField(name: 'name', label: 'Nombre', kind: FieldType::Text, required: true, maxLength: 80),
                new ParametricField(name: 'abbreviation', label: 'Abreviatura', kind: FieldType::Text, maxLength: 10, hint: 'Cómo se muestra junto al número, por ejemplo CC 123.'),
                new ParametricField(name: 'holder_type', label: 'Tipo de titular', kind: FieldType::Select, required: true, options: self::casesOf(HolderType::class)),
            ],
            headline: 'code',
            headlineLabel: 'Código',
            uniqueAttribute: 'code',
            uniqueLabel: 'código',
            primaryAttribute: 'name',
            usageRelation: 'workers',
            usageLabel: 'trabajador',
            searchAttributes: ['code', 'name', 'abbreviation'],
        );
    }

    private static function positions(): ParametricDefinition
    {
        return new ParametricDefinition(
            slug: 'cargos',
            model: Position::class,
            table: 'positions',
            title: 'Cargos',
            one: 'cargo',
            description: 'Catálogo de cargos que se asignan a cada contrato y aparecen en el formato de liquidación.',
            fields: [
                new ParametricField(name: 'code', label: 'Código', kind: FieldType::Text, required: true, maxLength: 20),
                new ParametricField(name: 'name', label: 'Cargo', kind: FieldType::Text, required: true, maxLength: 100),
                new ParametricField(name: 'area', label: 'Área', kind: FieldType::Text, maxLength: 80, hint: 'Área u operación a la que reporta el cargo.'),
                new ParametricField(name: 'level', label: 'Nivel', kind: FieldType::Text, maxLength: 40, hint: 'Por ejemplo operativo, asistencial o liderazgo.'),
            ],
            headline: 'code',
            headlineLabel: 'Código',
            uniqueAttribute: 'code',
            uniqueLabel: 'código',
            primaryAttribute: 'name',
            usageRelation: 'contracts',
            usageLabel: 'contrato',
            searchAttributes: ['code', 'name', 'area'],
        );
    }

    private static function contractTypes(): ParametricDefinition
    {
        return new ParametricDefinition(
            slug: 'tipos-de-contrato',
            model: ContractType::class,
            table: 'contract_types',
            title: 'Tipos de contrato',
            one: 'tipo de contrato',
            description: 'Modalidades contractuales y su regla de indemnización conforme al artículo 64 del CST.',
            fields: [
                new ParametricField(name: 'code', label: 'Código', kind: FieldType::Text, required: true, maxLength: 20),
                new ParametricField(name: 'name', label: 'Nombre', kind: FieldType::Text, required: true, maxLength: 100),
                new ParametricField(name: 'modality', label: 'Modalidad', kind: FieldType::Select, required: true, options: self::casesOf(ContractModality::class)),
                new ParametricField(name: 'statutory_max_months', label: 'Plazo máximo legal (meses)', kind: FieldType::Number, max: 120, hint: 'Déjelo vacío si la modalidad no tiene plazo máximo legal.'),
                new ParametricField(name: 'requires_scheduled_end', label: 'Exige fecha de terminación prevista', kind: FieldType::Checkbox),
                new ParametricField(name: 'indemnization_rule', label: 'Regla de indemnización', kind: FieldType::Select, required: true, options: self::casesOf(IndemnizationRule::class), hint: 'Cómo el motor calcula la indemnización cuando el motivo de retiro no define su propia regla.'),
            ],
            headline: 'code',
            headlineLabel: 'Código',
            uniqueAttribute: 'code',
            uniqueLabel: 'código',
            primaryAttribute: 'name',
            usageRelation: 'contracts',
            usageLabel: 'contrato',
            searchAttributes: ['code', 'name'],
        );
    }

    private static function terminationReasons(): ParametricDefinition
    {
        return new ParametricDefinition(
            slug: 'motivos-de-retiro',
            model: TerminationReason::class,
            table: 'termination_reasons',
            title: 'Motivos de retiro',
            one: 'motivo de retiro',
            description: 'Causales de terminación que deciden si la liquidación incluye indemnización o sanción.',
            fields: [
                new ParametricField(name: 'code', label: 'Código', kind: FieldType::Text, required: true, maxLength: 20),
                new ParametricField(name: 'description', label: 'Descripción', kind: FieldType::Text, required: true, maxLength: 120),
                new ParametricField(name: 'classification', label: 'Clasificación', kind: FieldType::Select, required: true, options: self::casesOf(TerminationClassification::class)),
                new ParametricField(name: 'indemnization_rule', label: 'Regla de indemnización', kind: FieldType::Select, required: true, options: self::casesOf(IndemnizationRule::class), hint: 'Con "ninguna" el motivo no genera indemnización.'),
                new ParametricField(name: 'applies_sanction_art65', label: 'Aplica sanción del artículo 65', kind: FieldType::Checkbox),
            ],
            headline: 'code',
            headlineLabel: 'Código',
            uniqueAttribute: 'code',
            uniqueLabel: 'código',
            primaryAttribute: 'description',
            usageRelation: 'contracts',
            usageLabel: 'contrato',
            searchAttributes: ['code', 'description'],
        );
    }

    private static function minimumWages(): ParametricDefinition
    {
        return new ParametricDefinition(
            slug: 'salarios-minimos',
            model: MinimumWage::class,
            table: 'minimum_wages',
            title: 'Salarios mínimos',
            one: 'salario mínimo',
            description: 'Valores del SMLMV y el auxilio de transporte por año, insumo del motor de liquidación.',
            fields: [
                new ParametricField(name: 'year', label: 'Año', kind: FieldType::Number, required: true, max: 2100, hint: 'Año de vigencia del salario mínimo.'),
                new ParametricField(name: 'smlmv', label: 'SMLMV mensual', kind: FieldType::Number, required: true, max: 99999999),
                new ParametricField(name: 'transport_auxiliary', label: 'Auxilio de transporte', kind: FieldType::Number, required: true, max: 99999999),
                new ParametricField(name: 'source', label: 'Fuente / referencia', kind: FieldType::Text, maxLength: 120, hint: 'Decreto o documento donde se publicó el valor.'),
            ],
            headline: 'year',
            headlineLabel: 'Año',
            uniqueAttribute: 'year',
            uniqueLabel: 'año',
            primaryAttribute: 'smlmv',
            usageRelation: null,
            usageLabel: null,
            hasPauseState: false,
            searchAttributes: ['year'],
        );
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private static function casesOf(string $enum): array
    {
        return array_map(
            fn ($case): array => ['value' => $case->value, 'label' => $case->label()],
            $enum::cases(),
        );
    }
}
