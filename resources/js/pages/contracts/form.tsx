import { Head, Link, useForm } from '@inertiajs/react';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { day, count, money } from '@/lib/format';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { destroy, index, store, update } from '@/routes/contracts';
import type { ContractForm, NumberedOption, Option } from '@/types';

type FormData = {
    code: string;
    worker_id: string;
    company_id: string;
    contract_type_id: string;
    position_id: string;
    termination_reason_id: string;
    start_date: string;
    end_date: string;
    scheduled_end_date: string;
    salary_effective_from: string;
    base_salary_monthly: string;
    transport_auxiliary_mode: string;
    transport_auxiliary_monthly: string;
    overtime_average_monthly: string;
    status: string;
    pending_salary_days: string;
    pending_vacation_days: string;
    notes: string;
};

const noReason = 'sin_causal';

type Props = {
    contract: ContractForm | null;
    suggestedCode: string | null;
    workers: NumberedOption[];
    companies: NumberedOption[];
    contractTypes: NumberedOption[];
    positions: NumberedOption[];
    terminationReasons: NumberedOption[];
    statuses: Option[];
    auxiliaryModes: Option[];
};

export default function ContractForm({
    contract,
    suggestedCode,
    workers,
    companies,
    contractTypes,
    positions,
    terminationReasons,
    statuses,
    auxiliaryModes,
}: Props) {
    const route = contract ? update(contract.id) : store();
    const isClosed = contract
        ? ['terminado', 'en_liquidacion', 'liquidado'].includes(contract.status)
        : false;

    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        code: contract?.code ?? suggestedCode ?? '',
        worker_id: String(contract?.worker_id ?? workers[0]?.value ?? ''),
        company_id: String(contract?.company_id ?? companies[0]?.value ?? ''),
        contract_type_id: String(
            contract?.contract_type_id ?? contractTypes[0]?.value ?? '',
        ),
        position_id: String(contract?.position_id ?? positions[0]?.value ?? ''),
        termination_reason_id: contract?.termination_reason_id
            ? String(contract.termination_reason_id)
            : noReason,
        start_date: contract?.dates.start ?? '',
        end_date: contract?.dates.end ?? '',
        scheduled_end_date: contract?.dates.scheduled_end ?? '',
        salary_effective_from: contract?.dates.salary_effective_from ?? '',
        base_salary_monthly: contract?.base_salary_monthly ?? '',
        transport_auxiliary_mode: contract?.transport_auxiliary_mode ?? 'auto',
        transport_auxiliary_monthly:
            contract?.transport_auxiliary_monthly ?? '',
        overtime_average_monthly: contract?.overtime_average_monthly ?? '',
        status: contract?.status ?? 'activo',
        pending_salary_days: String(contract?.pending_salary_days ?? 0),
        pending_vacation_days: String(contract?.pending_vacation_days ?? 0),
        notes: contract?.notes ?? '',
    });

    const field = (key: keyof FormData, value: string) =>
        setData((current) => ({ ...current, [key]: value }));

    const auxiliaryMode = data.transport_auxiliary_mode;

    return (
        <>
            <Head
                title={
                    contract ? `Editar ${contract.code}` : 'Registrar contrato'
                }
            />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={
                            contract ? 'Editar contrato' : 'Registrar contrato'
                        }
                        description="Ingreso, retiro, cargo y salario del contrato que la liquidación usará como base."
                    />
                    <Button asChild variant="ghost" size="sm">
                        <Link href={index()}>Volver a la lista</Link>
                    </Button>
                </div>

                <form
                    className="neu-card space-y-5 p-5 md:p-7"
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (contract) {
                            put(route.url, { preserveScroll: true });

                            return;
                        }

                        post(route.url, { preserveScroll: true });
                    }}
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Entry
                            id="code"
                            label="Código del contrato"
                            value={data.code}
                            error={errors.code}
                            onChange={(value) => field('code', value)}
                            hint="Ejemplo CTR-000001; se sugiere uno nuevo."
                        />
                        <Choice
                            id="worker_id"
                            label="Trabajador"
                            value={data.worker_id}
                            options={workers}
                            placeholder="Seleccionar trabajador"
                            error={errors.worker_id}
                            onChange={(value) => field('worker_id', value)}
                        />
                        <Choice
                            id="company_id"
                            label="Empresa"
                            value={data.company_id}
                            options={companies}
                            placeholder="Seleccionar empresa"
                            error={errors.company_id}
                            onChange={(value) => field('company_id', value)}
                        />
                        <Choice
                            id="contract_type_id"
                            label="Tipo de contrato"
                            value={data.contract_type_id}
                            options={contractTypes}
                            placeholder="Seleccionar tipo"
                            error={errors.contract_type_id}
                            onChange={(value) =>
                                field('contract_type_id', value)
                            }
                        />
                        <Choice
                            id="position_id"
                            label="Cargo"
                            value={data.position_id}
                            options={positions}
                            placeholder="Seleccionar cargo"
                            error={errors.position_id}
                            onChange={(value) => field('position_id', value)}
                        />
                        <Entry
                            id="start_date"
                            label="Fecha de ingreso"
                            type="date"
                            value={data.start_date}
                            error={errors.start_date}
                            onChange={(value) => field('start_date', value)}
                        />
                        <Entry
                            id="end_date"
                            label="Fecha de retiro"
                            type="date"
                            value={data.end_date}
                            error={errors.end_date}
                            onChange={(value) => field('end_date', value)}
                        />
                        <Entry
                            id="scheduled_end_date"
                            label="Fecha de terminación prevista"
                            type="date"
                            value={data.scheduled_end_date}
                            error={errors.scheduled_end_date}
                            onChange={(value) =>
                                field('scheduled_end_date', value)
                            }
                            hint="Obligatoria en términos fijos y obras."
                        />
                        <Entry
                            id="salary_effective_from"
                            label="Salario vigente desde"
                            type="date"
                            value={data.salary_effective_from}
                            error={errors.salary_effective_from}
                            onChange={(value) =>
                                field('salary_effective_from', value)
                            }
                        />
                        <Entry
                            id="base_salary_monthly"
                            label="Salario básico mensual"
                            value={data.base_salary_monthly}
                            error={errors.base_salary_monthly}
                            onChange={(value) =>
                                field('base_salary_monthly', value)
                            }
                        />
                        <Choice
                            id="transport_auxiliary_mode"
                            label="Auxilio de transporte"
                            value={data.transport_auxiliary_mode}
                            options={auxiliaryModes}
                            placeholder="Modo"
                            error={errors.transport_auxiliary_mode}
                            onChange={(value) =>
                                field('transport_auxiliary_mode', value)
                            }
                            hint={
                                auxiliaryMode === 'auto'
                                    ? 'Se toma del catálogo de salarios mínimos del año.'
                                    : undefined
                            }
                        />
                        {auxiliaryMode === 'manual' ? (
                            <Entry
                                id="transport_auxiliary_monthly"
                                label="Auxilio mensual digitado"
                                value={data.transport_auxiliary_monthly}
                                error={errors.transport_auxiliary_monthly}
                                onChange={(value) =>
                                    field('transport_auxiliary_monthly', value)
                                }
                            />
                        ) : null}
                        <Entry
                            id="overtime_average_monthly"
                            label="Promedio mensual de horas extras y recargos"
                            value={data.overtime_average_monthly}
                            error={errors.overtime_average_monthly}
                            onChange={(value) =>
                                field('overtime_average_monthly', value)
                            }
                        />

                        <Choice
                            id="termination_reason_id"
                            label="Motivo de retiro"
                            value={data.termination_reason_id}
                            options={terminationReasons}
                            placeholder="Seleccionar motivo"
                            error={errors.termination_reason_id}
                            onChange={(value) =>
                                field('termination_reason_id', value)
                            }
                            emptyLabel={noReason}
                            hint={
                                isClosed
                                    ? 'El motivo y la fecha de retiro son obligatorios en contratos cerrados.'
                                    : undefined
                            }
                        />
                        {contract ? (
                            <Choice
                                id="status"
                                label="Estado"
                                value={data.status}
                                options={statuses}
                                placeholder="Estado"
                                error={errors.status}
                                onChange={(value) => field('status', value)}
                            />
                        ) : null}
                        <Entry
                            id="pending_salary_days"
                            label="Días de salario pendiente"
                            value={data.pending_salary_days}
                            error={errors.pending_salary_days}
                            onChange={(value) =>
                                field('pending_salary_days', value)
                            }
                        />
                        <Entry
                            id="pending_vacation_days"
                            label="Días de vacaciones pendientes"
                            value={data.pending_vacation_days}
                            error={errors.pending_vacation_days}
                            onChange={(value) =>
                                field('pending_vacation_days', value)
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="notes">Observaciones (opcional)</Label>
                        <textarea
                            id="notes"
                            value={data.notes}
                            onChange={(event) =>
                                field('notes', event.target.value)
                            }
                            className="mt-1 min-h-24 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm"
                        />
                        <InputError message={errors.notes} className="mt-1" />
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button
                            type="submit"
                            className="btn-taxi border-0"
                            disabled={processing}
                        >
                            {contract
                                ? 'Guardar cambios'
                                : 'Registrar contrato'}
                        </Button>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={index()}>Cancelar</Link>
                        </Button>
                    </div>
                </form>

                {contract ? (
                    <div className="neu-card p-5">
                        <p className="text-sm font-medium">Historial</p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {contract.creator
                                ? `Registrado por ${contract.creator}. `
                                : ''}
                            Ingreso {day(contract.dates.start)}
                            {contract.dates.end
                                ? ` · Retiro ${day(contract.dates.end)}`
                                : ''}
                            {' · '}
                            {count(
                                contract.liquidations_count,
                                'liquidación',
                                'liquidaciones',
                            )}
                            {contract.base_salary_monthly
                                ? ` · Salario ${money(contract.base_salary_monthly)}`
                                : ''}
                        </p>
                        {contract.liquidations_count > 0 ? (
                            <p className="mt-2 text-xs text-muted-foreground">
                                El contrato ya tiene liquidaciones: no se puede
                                volver a activo ni eliminar.
                            </p>
                        ) : null}
                    </div>
                ) : null}

                {contract && contract.liquidations_count === 0 ? (
                    <div className="neu-card flex flex-wrap items-center justify-between gap-4 border-l-4 border-l-destructive/70 p-5">
                        <div>
                            <p className="text-sm font-medium">
                                Eliminar este contrato
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Al eliminarlo desaparece de los listados, pero
                                conserva el historial gracias al borrado suave.
                            </p>
                        </div>
                        <ConfirmDeleteDialog
                            action={destroy(contract.id).url}
                            label="Eliminar contrato"
                            question={`¿Eliminar el contrato ${contract.code} de ${contract.worker_name}?`}
                            description="Si el contrato solo se cerró por error, corríjalo aquí; en caso contrario, elimine."
                        />
                    </div>
                ) : null}
            </div>
        </>
    );
}

function Entry({
    id,
    label,
    value,
    error,
    onChange,
    hint,
    type = 'text',
}: {
    id: string;
    label: string;
    value: string;
    error?: string;
    onChange: (value: string) => void;
    hint?: string;
    type?: string;
}) {
    return (
        <div>
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                type={type}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-1"
            />
            {hint ? (
                <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
            ) : null}
            <InputError message={error} className="mt-1" />
        </div>
    );
}

function Choice({
    id,
    label,
    value,
    options,
    placeholder,
    error,
    onChange,
    hint,
    emptyLabel,
}: {
    id: string;
    label: string;
    value: string;
    options: Option[] | NumberedOption[];
    placeholder: string;
    error?: string;
    onChange: (value: string) => void;
    hint?: string;
    emptyLabel?: string;
}) {
    return (
        <div>
            <Label htmlFor={id}>{label}</Label>
            <Select
                value={value}
                onValueChange={(selected) =>
                    onChange(selected === emptyLabel ? '' : selected)
                }
            >
                <SelectTrigger id={id} className="mt-1 w-full">
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {emptyLabel ? (
                        <SelectItem value={emptyLabel}>
                            {placeholder}
                        </SelectItem>
                    ) : null}
                    {options.map((option) => (
                        <SelectItem
                            key={option.value}
                            value={String(option.value)}
                        >
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {hint ? (
                <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
            ) : null}
            <InputError message={error} className="mt-1" />
        </div>
    );
}
