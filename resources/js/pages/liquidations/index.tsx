import { Head, Link, router } from '@inertiajs/react';
import { FileSpreadsheet } from 'lucide-react';
import { useState } from 'react';
import { Pager } from '@/components/pager';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { day, money } from '@/lib/format';
import { index, show, create, excel } from '@/routes/liquidations';
import type { ContractCard, Option, Paginated } from '@/types';

type Filters = {
    status: string | null;
    document_type_id: number | null;
    document_number: string | null;
};

type Props = {
    contracts: Paginated<ContractCard>;
    filters: Filters;
    statuses: Option[];
    documentTypes: { value: number; label: string }[];
};

type Draft = {
    status: string;
    document_type_id: string;
    document_number: string;
};

const emptyDraft: Draft = {
    status: '',
    document_type_id: '',
    document_number: '',
};

export default function LiquidationIndex({
    contracts,
    filters,
    statuses,
    documentTypes,
}: Props) {
    const [draft, setDraft] = useState<Draft>({
        status: filters.status ?? '',
        document_type_id: filters.document_type_id
            ? String(filters.document_type_id)
            : '',
        document_number: filters.document_number ?? '',
    });

    const send = (next: Draft) => {
        setDraft(next);

        const query = Object.fromEntries(
            Object.entries(next).filter(([, value]) => value !== ''),
        );

        router.get(index().url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Liquidaciones" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Liquidaciones"
                    description="Filtre por estado del contrato, tipo y número de documento del trabajador. Cada contrato liquidable abre la calculadora."
                />

                <form
                    className="neu-card grid gap-4 p-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.2fr)_auto] md:items-end"
                    onSubmit={(event) => {
                        event.preventDefault();
                        send(draft);
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="status">Estado del contrato</Label>
                        <select
                            id="status"
                            className="neu-sunken h-10 rounded-xl px-3 text-sm outline-none"
                            value={draft.status}
                            onChange={(event) =>
                                send({ ...draft, status: event.target.value })
                            }
                        >
                            <option value="">Todos</option>
                            {statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="document_type_id">
                            Tipo de documento
                        </Label>
                        <select
                            id="document_type_id"
                            className="neu-sunken h-10 rounded-xl px-3 text-sm outline-none"
                            value={draft.document_type_id}
                            onChange={(event) =>
                                send({
                                    ...draft,
                                    document_type_id: event.target.value,
                                })
                            }
                        >
                            <option value="">Todos</option>
                            {documentTypes.map((type) => (
                                <option
                                    key={type.value}
                                    value={String(type.value)}
                                >
                                    {type.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="document_number">
                            Número de documento
                        </Label>
                        <Input
                            id="document_number"
                            className="neu-sunken h-10 rounded-xl border-0"
                            placeholder="1098760001"
                            value={draft.document_number}
                            onChange={(event) =>
                                setDraft({
                                    ...draft,
                                    document_number: event.target.value,
                                })
                            }
                        />
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit" className="btn-taxi border-0">
                            Buscar
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => send(emptyDraft)}
                        >
                            Limpiar
                        </Button>
                    </div>
                </form>

                <p className="tabular text-sm text-muted-foreground">
                    {contracts.total}{' '}
                    {contracts.total === 1 ? 'contrato' : 'contratos'}
                    {contracts.from
                        ? ` · del ${contracts.from} al ${contracts.to}`
                        : ''}
                </p>

                {contracts.data.length === 0 ? (
                    <div className="neu-card p-8 text-center text-sm text-muted-foreground">
                        Ningún contrato coincide con el filtro. Ajuste el estado
                        o el documento.
                    </div>
                ) : (
                    <>
                        <div className="grid gap-3 md:hidden">
                            {contracts.data.map((contract) => (
                                <ContractSummary
                                    key={contract.id}
                                    contract={contract}
                                />
                            ))}
                        </div>

                        <div className="neu-card hidden overflow-x-auto md:block">
                            <table className="tabular w-full min-w-[56rem] text-sm">
                                <thead className="bg-muted/60 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Trabajador
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Empresa
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Contrato
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Periodo
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Salario
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Estado
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Liquidaciones
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {contracts.data.map((contract) => (
                                        <tr
                                            key={contract.id}
                                            className="border-t border-border/60"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="font-medium">
                                                    {contract.worker.name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {contract.worker.document}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                {contract.company}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div>{contract.code}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {contract.modality ??
                                                        contract.contract_type}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                {day(contract.dates.start)} →{' '}
                                                {day(contract.dates.end)}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {money(contract.salary.basic)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge
                                                    contract={contract}
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                <FolioLinks
                                                    contract={contract}
                                                    empty="Sin folios"
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {contract.can_liquidate ? (
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        className="btn-taxi border-0"
                                                    >
                                                        <Link
                                                            href={create(
                                                                contract.id,
                                                            )}
                                                        >
                                                            Liquidar
                                                        </Link>
                                                    </Button>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        No liquidable
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}

                <Pager page={contracts} label="Paginación de liquidaciones" />
            </div>
        </>
    );
}

function StatusBadge({ contract }: { contract: ContractCard }) {
    return (
        <Badge
            variant={
                contract.status.value === 'liquidado' ? 'secondary' : 'default'
            }
        >
            {contract.status.label}
        </Badge>
    );
}

function ContractSummary({ contract }: { contract: ContractCard }) {
    return (
        <article className="neu-card neu-enter p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-medium">{contract.worker.name}</p>
                    <p className="tabular text-xs text-muted-foreground">
                        {contract.worker.document} · {contract.code}
                    </p>
                </div>
                <StatusBadge contract={contract} />
            </div>

            <dl className="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <div>{contract.company}</div>
                <div className="tabular text-right">
                    {money(contract.salary.basic)}
                </div>
                <div>
                    {day(contract.dates.start)} → {day(contract.dates.end)}
                </div>
                <div className="text-right">{contract.modality}</div>
            </dl>

            {contract.can_liquidate ? (
                <Button
                    asChild
                    size="sm"
                    className="btn-taxi mt-4 w-full border-0"
                >
                    <Link href={create(contract.id)}>
                        Liquidar este contrato
                    </Link>
                </Button>
            ) : null}

            {contract.liquidations.length > 0 ? (
                <p className="mt-3 text-xs text-muted-foreground">
                    <FolioLinks contract={contract} />
                </p>
            ) : null}
        </article>
    );
}

function FolioLinks({
    contract,
    empty,
}: {
    contract: ContractCard;
    empty?: string;
}) {
    if (contract.liquidations.length === 0) {
        return <span className="text-muted-foreground">{empty}</span>;
    }

    return contract.liquidations.map((liquidation) => (
        <span
            key={liquidation.id}
            className="mr-2 inline-flex items-center gap-1"
        >
            <Link
                href={show(liquidation.id)}
                className="underline underline-offset-2"
            >
                {liquidation.folio}
            </Link>
            <a
                href={excel.url(liquidation.id)}
                download
                aria-label={`Descargar el Excel de la liquidación ${liquidation.folio}`}
                className="text-muted-foreground hover:text-foreground"
            >
                <FileSpreadsheet className="size-3.5" />
            </a>
        </span>
    ));
}
