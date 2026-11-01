import { Head, Link, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { day, money } from '@/lib/format';
import { index, show, store } from '@/routes/liquidations';
import type { ContractCard, Option } from '@/types';

type ManualConcept = {
    code: string;
    name: string;
    side: string;
};

type FormData = {
    settlement_date: string;
    variant: string;
    cesantias_days: string;
    prima_days: string;
    vacation_days: string;
    pending_salary_days: string;
    manual_values: Record<string, string>;
    observations: string;
};

type Props = {
    contract: ContractCard;
    variants: Option[];
    suggestedVariant: string;
    manualConcepts: ManualConcept[];
};

export default function LiquidationCreate({
    contract,
    variants,
    suggestedVariant,
    manualConcepts,
}: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        settlement_date: contract.dates.end ?? contract.dates.start,
        variant: suggestedVariant,
        cesantias_days: '',
        prima_days: '',
        vacation_days: String(contract.pending_days.vacations),
        pending_salary_days: String(contract.pending_days.salary),
        manual_values: Object.fromEntries(
            manualConcepts.map((concept) => [concept.code, '']),
        ),
        observations: '',
    });

    const field = (key: keyof FormData, value: string) =>
        setData((current) => ({ ...current, [key]: value }));

    return (
        <>
            <Head title={`Liquidar contrato ${contract.code}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Calculadora de liquidación"
                        description={`Contrato ${contract.code} · ${contract.worker.name}`}
                    />
                    <Button asChild variant="ghost" size="sm">
                        <Link href={index()}>Volver a la lista</Link>
                    </Button>
                </div>

                <form
                    className="grid gap-6 lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)]"
                    onSubmit={(event) => {
                        event.preventDefault();
                        post(store(contract.id).url, { preserveScroll: true });
                    }}
                >
                    <aside className="neu-card h-fit space-y-4 p-5">
                        <div className="flex items-center justify-between gap-2">
                            <h2 className="font-semibold">
                                {contract.worker.name}
                            </h2>
                            <Badge
                                variant={
                                    contract.status.value === 'liquidado'
                                        ? 'secondary'
                                        : 'default'
                                }
                            >
                                {contract.status.label}
                            </Badge>
                        </div>

                        <dl className="space-y-2 text-sm">
                            <Definition
                                label="Documento"
                                value={contract.worker.document}
                            />
                            <Definition
                                label="Empresa"
                                value={contract.company}
                            />
                            <Definition
                                label="Cargo"
                                value={contract.position ?? '—'}
                            />
                            <Definition
                                label="Modalidad"
                                value={
                                    contract.modality ??
                                    contract.contract_type ??
                                    '—'
                                }
                            />
                            <Definition
                                label="Inicio"
                                value={day(contract.dates.start)}
                            />
                            <Definition
                                label="Fin"
                                value={
                                    contract.dates.end
                                        ? day(contract.dates.end)
                                        : 'Sin fecha de fin'
                                }
                            />
                            {contract.termination_reason ? (
                                <Definition
                                    label="Causa de retiro"
                                    value={contract.termination_reason}
                                />
                            ) : null}
                        </dl>

                        <div className="neu-sunken rounded-xl p-4">
                            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                Bases del contrato
                            </p>
                            <ul className="tabular mt-2 space-y-1 text-sm">
                                <li className="flex justify-between">
                                    <span>Salario básico</span>
                                    <strong>
                                        {money(contract.salary.basic)}
                                    </strong>
                                </li>
                                <li className="flex justify-between">
                                    <span>Auxilio transporte</span>
                                    <strong>
                                        {money(contract.salary.auxiliary)}
                                    </strong>
                                </li>
                                <li className="flex justify-between">
                                    <span>Promedio variables</span>
                                    <strong>
                                        {money(
                                            contract.salary.overtime_average,
                                        )}
                                    </strong>
                                </li>
                            </ul>
                        </div>

                        <p className="text-xs text-muted-foreground">
                            Los días del año comercial, el salario mínimo y los
                            parámetros legales se toman de las tablas
                            paramétricas según la fecha de liquidación.
                        </p>
                    </aside>

                    <div className="space-y-6">
                        <section className="neu-card space-y-5 p-5">
                            <h2 className="font-semibold">Datos del cálculo</h2>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="settlement_date">
                                        Fecha de liquidación
                                    </Label>
                                    <Input
                                        id="settlement_date"
                                        type="date"
                                        className="neu-sunken h-10 rounded-xl border-0"
                                        value={data.settlement_date}
                                        onChange={(event) =>
                                            field(
                                                'settlement_date',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.settlement_date}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="variant">Formato</Label>
                                    <select
                                        id="variant"
                                        className="neu-sunken h-10 rounded-xl px-3 text-sm outline-none"
                                        value={data.variant}
                                        onChange={(event) =>
                                            field('variant', event.target.value)
                                        }
                                    >
                                        {variants.map((variant) => (
                                            <option
                                                key={variant.value}
                                                value={variant.value}
                                            >
                                                {variant.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.variant} />
                                </div>
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                                <DayField
                                    id="cesantias_days"
                                    label="Días de cesantías"
                                    hint="Vacío: año comercial"
                                    value={data.cesantias_days}
                                    error={errors.cesantias_days}
                                    onChange={(value) =>
                                        field('cesantias_days', value)
                                    }
                                />
                                <DayField
                                    id="prima_days"
                                    label="Días de prima"
                                    hint="Vacío: año comercial"
                                    value={data.prima_days}
                                    error={errors.prima_days}
                                    onChange={(value) =>
                                        field('prima_days', value)
                                    }
                                />
                                <DayField
                                    id="vacation_days"
                                    label="Días de vacaciones"
                                    value={data.vacation_days}
                                    error={errors.vacation_days}
                                    onChange={(value) =>
                                        field('vacation_days', value)
                                    }
                                />
                                <DayField
                                    id="pending_salary_days"
                                    label="Días de salario"
                                    value={data.pending_salary_days}
                                    error={errors.pending_salary_days}
                                    onChange={(value) =>
                                        field('pending_salary_days', value)
                                    }
                                />
                            </div>
                        </section>

                        <section className="neu-card space-y-5 p-5">
                            <h2 className="font-semibold">
                                Valores que se digitan a mano
                            </h2>

                            <div className="grid gap-5 sm:grid-cols-2">
                                {manualConcepts.map((concept) => (
                                    <div
                                        key={concept.code}
                                        className="grid gap-2"
                                    >
                                        <Label htmlFor={concept.code}>
                                            {concept.name} ({concept.side})
                                        </Label>
                                        <Input
                                            id={concept.code}
                                            inputMode="numeric"
                                            className="neu-sunken tabular h-10 rounded-xl border-0"
                                            placeholder="0"
                                            value={
                                                data.manual_values[
                                                    concept.code
                                                ] ?? ''
                                            }
                                            onChange={(event) =>
                                                setData((current) => ({
                                                    ...current,
                                                    manual_values: {
                                                        ...current.manual_values,
                                                        [concept.code]:
                                                            event.target.value,
                                                    },
                                                }))
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `manual_values.${concept.code}`
                                                ]
                                            }
                                        />
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className="neu-card space-y-3 p-5">
                            <Label htmlFor="observations">
                                Observaciones del documento
                            </Label>
                            <textarea
                                id="observations"
                                rows={3}
                                className="neu-sunken w-full rounded-xl p-3 text-sm outline-none"
                                placeholder="Se imprimen en el literal de la liquidación."
                                value={data.observations}
                                onChange={(event) =>
                                    field('observations', event.target.value)
                                }
                            />
                            <InputError message={errors.observations} />
                        </section>

                        <div className="flex flex-wrap items-center gap-3">
                            <Button
                                type="submit"
                                className="btn-taxi border-0"
                                disabled={processing}
                            >
                                {processing
                                    ? 'Calculando…'
                                    : 'Calcular y guardar'}
                            </Button>
                            <p className="text-xs text-muted-foreground">
                                El cálculo queda guardado con folio propio y
                                congelando los parámetros usados.
                            </p>
                        </div>

                        {contract.liquidations.length > 0 ? (
                            <p className="text-xs text-muted-foreground">
                                Ya existen folios para este contrato:{' '}
                                {contract.liquidations.map(
                                    (liquidation, position) => (
                                        <span key={liquidation.id}>
                                            {position > 0 ? ', ' : ''}
                                            <Link
                                                href={show(liquidation.id)}
                                                className="underline underline-offset-2"
                                            >
                                                {liquidation.folio}
                                            </Link>
                                        </span>
                                    ),
                                )}
                            </p>
                        ) : null}
                    </div>
                </form>
            </div>
        </>
    );
}

function Definition({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-3">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-right">{value}</dd>
        </div>
    );
}

function DayField({
    id,
    label,
    hint,
    value,
    error,
    onChange,
}: {
    id: string;
    label: string;
    hint?: string;
    value: string;
    error?: string;
    onChange: (value: string) => void;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                inputMode="numeric"
                className="neu-sunken tabular h-10 rounded-xl border-0"
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
            {hint ? (
                <p className="text-xs text-muted-foreground">{hint}</p>
            ) : null}
            <InputError message={error} />
        </div>
    );
}
