import { Head, Link, usePage } from '@inertiajs/react';
import { Building2, Calculator, FileSpreadsheet, Users } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { day, money } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as contracts } from '@/routes/contracts';
import {
    create as liquidate,
    index as liquidations,
} from '@/routes/liquidations';
import type { ContractCard } from '@/types';

type Summary = {
    active: number;
    liquidatable: number;
    liquidated: number;
    liquidations: number;
};

type Props = {
    summary: Summary;
    pending: ContractCard[];
};

const cards = (summary: Summary) => [
    {
        label: 'Contratos activos',
        value: summary.active,
        hint: 'Aprendiendo prestaciones mes a mes',
    },
    {
        label: 'Listos para liquidar',
        value: summary.liquidatable,
        hint: 'Terminados o en liquidación',
    },
    {
        label: 'Contratos liquidados',
        value: summary.liquidated,
        hint: 'Con las prestaciones pagadas',
    },
    {
        label: 'Liquidaciones guardadas',
        value: summary.liquidations,
        hint: 'Documentos con folio propio',
    },
];

export default function Dashboard({ summary, pending }: Props) {
    const user = usePage().props.auth.user;

    return (
        <>
            <Head title="Panel" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Heading
                    title={`Hola, ${user.name.split(' ')[0]}`}
                    description="Resumen de los contratos que puede ver en Blindem. Desde aquí se entra a la calculadora de liquidaciones."
                />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {cards(summary).map((card) => (
                        <article key={card.label} className="neu-card p-5">
                            <p className="text-sm text-muted-foreground">
                                {card.label}
                            </p>
                            <p className="tabular mt-2 text-3xl font-semibold">
                                {card.value}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {card.hint}
                            </p>
                        </article>
                    ))}
                </div>

                <div className="flex flex-wrap gap-3">
                    <Button asChild className="btn-taxi border-0">
                        <Link href={liquidations()}>
                            <Calculator />
                            Calcular una liquidación
                        </Link>
                    </Button>
                    <Button asChild variant="outline">
                        <Link href={contracts()}>
                            <FileSpreadsheet />
                            Ver contratos
                        </Link>
                    </Button>
                </div>

                <section className="neu-card p-5">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 className="text-base font-medium">
                                Contratos por liquidar
                            </h2>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Los retiros más antiguos primero; son los que
                                generan intereses corrientes.
                            </p>
                        </div>
                        <Badge
                            variant={pending.length ? 'default' : 'secondary'}
                        >
                            {pending.length ? 'Atención' : 'Sin pendientes'}
                        </Badge>
                    </div>

                    {pending.length === 0 ? (
                        <p className="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Users />
                            No hay contratos terminados esperando liquidación.
                        </p>
                    ) : (
                        <ul className="mt-4 divide-y divide-border/60">
                            {pending.map((contract) => (
                                <li
                                    key={contract.id}
                                    className="flex flex-wrap items-center justify-between gap-3 py-3"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {contract.worker.name}
                                        </p>
                                        <p className="tabular mt-1 text-xs text-muted-foreground">
                                            <Building2 className="mr-1 inline size-3" />
                                            {contract.company} · {contract.code}
                                            {' · retiro '}
                                            {day(contract.dates.end)} ·{' '}
                                            {money(contract.salary.basic)}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge variant="outline">
                                            {contract.status.label}
                                        </Badge>
                                        {contract.can_liquidate ? (
                                            <Button
                                                asChild
                                                size="sm"
                                                className="btn-taxi border-0"
                                            >
                                                <Link
                                                    href={liquidate(
                                                        contract.id,
                                                    )}
                                                >
                                                    Liquidar
                                                </Link>
                                            </Button>
                                        ) : null}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Panel',
            href: dashboard(),
        },
    ],
};
