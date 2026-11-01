import { Head, Link } from '@inertiajs/react';
import { FileSpreadsheet } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { count, day, money, quantity } from '@/lib/format';
import { excel, index } from '@/routes/liquidations';
import type { ContractCard, LiquidationDocument } from '@/types/liquidations';

type Props = {
    liquidation: LiquidationDocument;
    contract: ContractCard;
};

export default function LiquidationShow({ liquidation, contract }: Props) {
    return (
        <>
            <Head title={`Liquidación ${liquidation.folio}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4 print:hidden">
                    <Heading
                        title={`Liquidación ${liquidation.folio}`}
                        description={`${liquidation.variant.label} · calculada por ${liquidation.creator ?? 'el sistema'}`}
                    />
                    <div className="flex gap-2">
                        <Button asChild size="sm" className="btn-taxi border-0">
                            <a href={excel.url(liquidation.id)} download>
                                <FileSpreadsheet />
                                Excel
                            </a>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.print()}
                        >
                            Imprimir
                        </Button>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={index()}>Volver</Link>
                        </Button>
                    </div>
                </div>

                <article className="neu-card space-y-6 p-5 md:p-8">
                    <header className="flex flex-wrap items-start justify-between gap-4 border-b border-border/60 pb-4">
                        <div>
                            <h2 className="text-lg font-semibold">
                                {contract.worker.name}
                            </h2>
                            <p className="tabular text-sm text-muted-foreground">
                                {contract.worker.document} · {contract.company}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {contract.contract_type} · {contract.position}
                            </p>
                        </div>
                        <div className="text-right">
                            <p className="tabular text-lg font-semibold">
                                {liquidation.folio}
                            </p>
                            <Badge
                                variant={
                                    liquidation.status.value === 'anulada'
                                        ? 'destructive'
                                        : 'default'
                                }
                            >
                                {liquidation.status.label}
                            </Badge>
                        </div>
                    </header>

                    <dl className="tabular grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <Field
                            label="Inicio del contrato"
                            value={day(contract.dates.start)}
                        />
                        <Field
                            label="Fecha de liquidación"
                            value={day(liquidation.dates.settlement)}
                        />
                        <Field
                            label="Días trabajados"
                            value={count(
                                liquidation.days.worked,
                                'día',
                                'días',
                            )}
                        />
                        <Field
                            label="Periodo liquidado"
                            value={`${day(liquidation.dates.period_from)} → ${day(liquidation.dates.period_to)}`}
                        />
                        <Field
                            label="Antigüedad"
                            value={
                                liquidation.tenure
                                    ? `${count(liquidation.tenure.anos_completos, 'año', 'años')} · ${count(liquidation.tenure.fraccion, 'día', 'días')}`
                                    : '—'
                            }
                        />
                        <Field
                            label="Causa de retiro"
                            value={
                                contract.termination_reason ?? 'No registrada'
                            }
                        />
                    </dl>

                    <div className="neu-sunken tabular grid gap-x-6 gap-y-2 rounded-xl p-4 text-sm sm:grid-cols-3">
                        <div>
                            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                Base prestacional
                            </p>
                            <p className="font-semibold">
                                {money(liquidation.bases.prestacional)}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                Base vacacional
                            </p>
                            <p className="font-semibold">
                                {money(liquidation.bases.vacacional)}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                SMLMV {liquidation.wages?.year ?? ''}
                            </p>
                            <p className="font-semibold">
                                {money(liquidation.bases.smlmv)}
                            </p>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="tabular w-full min-w-[42rem] text-sm">
                            <thead className="text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr className="border-b border-border/60">
                                    <th className="py-2 pr-3 font-medium">
                                        Concepto
                                    </th>
                                    <th className="py-2 pr-3 text-right font-medium">
                                        Base
                                    </th>
                                    <th className="py-2 pr-3 text-right font-medium">
                                        Días / cantidad
                                    </th>
                                    <th className="py-2 pr-3 text-right font-medium">
                                        Divisor
                                    </th>
                                    <th className="py-2 text-right font-medium">
                                        Valor
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {liquidation.lines.map((line) => (
                                    <tr
                                        key={line.code}
                                        className="border-b border-border/40 last:border-0"
                                    >
                                        <td className="py-2 pr-3">
                                            <p className="font-medium">
                                                {line.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {line.formula}
                                            </p>
                                        </td>
                                        <td className="py-2 pr-3 text-right">
                                            {money(line.base)}
                                        </td>
                                        <td className="py-2 pr-3 text-right">
                                            {quantity(line.quantity)}
                                        </td>
                                        <td className="py-2 pr-3 text-right">
                                            {line.divisor ?? '—'}
                                        </td>
                                        <td
                                            className={
                                                line.side.value === 'deduction'
                                                    ? 'py-2 text-right text-destructive'
                                                    : 'py-2 text-right'
                                            }
                                        >
                                            {line.side.value === 'deduction'
                                                ? '−'
                                                : ''}
                                            {money(line.amount)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-3">
                        <Total
                            label="Total devengado"
                            value={money(liquidation.totals.earned)}
                        />
                        <Total
                            label="Total deducciones"
                            value={money(liquidation.totals.deducted)}
                        />
                        <div className="btn-taxi rounded-xl p-4 text-right">
                            <p className="text-xs tracking-wide uppercase">
                                Neto a pagar
                            </p>
                            <p className="neu-figure text-2xl">
                                {money(liquidation.totals.net)}
                            </p>
                        </div>
                    </div>

                    {liquidation.indemnization ? (
                        <section className="neu-sunken rounded-xl p-4 text-sm print:hidden">
                            <p className="text-xs tracking-wide text-muted-foreground">
                                Regla de indemnización:{' '}
                                {liquidation.indemnization.regla}
                            </p>
                            <p className="mt-1">
                                {liquidation.indemnization.formula}
                            </p>
                            <p className="tabular mt-1 text-muted-foreground">
                                {liquidation.indemnization.detalle}
                            </p>
                        </section>
                    ) : null}

                    {liquidation.notices.length > 0 ? (
                        <section className="rounded-xl border border-destructive/40 p-4 text-sm">
                            <p className="font-medium">Avisos del cálculo</p>
                            <ul className="mt-2 list-disc space-y-1 pl-5 text-muted-foreground">
                                {liquidation.notices.map((notice) => (
                                    <li key={notice}>{notice}</li>
                                ))}
                            </ul>
                        </section>
                    ) : null}

                    {liquidation.observations ? (
                        <p className="text-sm text-muted-foreground">
                            {liquidation.observations}
                        </p>
                    ) : null}

                    <footer className="tabular flex flex-wrap justify-between gap-2 text-xs text-muted-foreground">
                        <span>
                            Días: cesantías {liquidation.days.cesantias} · prima{' '}
                            {liquidation.days.prima} · vacaciones{' '}
                            {liquidation.days.vacaciones} · salario{' '}
                            {liquidation.days.salario_pendiente}
                        </span>
                        <span>
                            {liquidation.captured_at
                                ? `Parámetros congelados el ${new Date(liquidation.captured_at).toLocaleString('es-CO')}`
                                : ''}
                        </span>
                    </footer>
                </article>

                <details className="neu-card p-5 text-sm print:hidden">
                    <summary className="cursor-pointer font-medium">
                        Parámetros usados en este folio
                    </summary>
                    <dl className="tabular mt-3 grid gap-x-6 gap-y-1 sm:grid-cols-2 lg:grid-cols-3">
                        {Object.entries(liquidation.parameters).map(
                            ([key, value]) => (
                                <div
                                    key={key}
                                    className="flex justify-between gap-3"
                                >
                                    <dt className="text-muted-foreground">
                                        {key}
                                    </dt>
                                    <dd>{value}</dd>
                                </div>
                            ),
                        )}
                    </dl>
                </details>
            </div>
        </>
    );
}

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd>{value}</dd>
        </div>
    );
}

function Total({ label, value }: { label: string; value: string }) {
    return (
        <div className="neu-sunken rounded-xl p-4 text-right">
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p className="tabular font-semibold">{value}</p>
        </div>
    );
}
