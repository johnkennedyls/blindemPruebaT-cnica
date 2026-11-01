import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Pager } from '@/components/pager';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { day, count, money } from '@/lib/format';
import { create, edit, index } from '@/routes/contracts';
import { create as liquidate } from '@/routes/liquidations';
import { show as showLiquidation } from '@/routes/liquidations';
import type { ContractCard, NumberedOption, Option, Paginated } from '@/types';

type Props = {
    contracts: Paginated<ContractCard>;
    filters: {
        q: string | null;
        status: string | null;
        company_id: number | null;
    };
    statuses: Option[];
    companies: NumberedOption[];
};

const statusColor: Record<string, string> = {
    activo: 'bg-emerald-500/90 text-white border-0',
    terminado: 'bg-amber-500/90 text-white border-0',
    en_liquidacion: 'bg-blue-500/90 text-white border-0',
    liquidado: 'bg-secondary text-secondary-foreground border-0',
};

export default function ContractsIndex({
    contracts,
    filters,
    statuses,
    companies,
}: Props) {
    const [term, setTerm] = useState(filters.q ?? '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status ?? '');
    const [selectedCompany, setSelectedCompany] = useState(
        filters.company_id ? String(filters.company_id) : '',
    );

    const applyFilter = (next: { status?: string; company_id?: string }) => {
        router.get(index().url, {
            q: term || undefined,
            status: (next.status ?? selectedStatus) || undefined,
            company_id: (next.company_id ?? selectedCompany) || undefined,
        });
    };

    return (
        <>
            <Head title="Contratos" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Contratos"
                        description="Historial laboral por afiliado y por empresa. El contrato alimenta el formato de liquidación."
                    />
                    <Button asChild size="sm" className="btn-taxi border-0">
                        <Link href={create()}>Registrar contrato</Link>
                    </Button>
                </div>

                <form
                    className="neu-card flex flex-wrap items-end gap-3 p-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        applyFilter({});
                    }}
                >
                    <div className="min-w-56 flex-1">
                        <Label htmlFor="contract-search">
                            Buscar por código, trabajador o documento
                        </Label>
                        <Input
                            id="contract-search"
                            value={term}
                            onChange={(event) => setTerm(event.target.value)}
                            className="mt-1"
                        />
                    </div>
                    <div className="w-44">
                        <Label htmlFor="contract-status">Estado</Label>
                        <SelectFilter
                            id="contract-status"
                            value={selectedStatus}
                            options={statuses}
                            placeholder="Todos"
                            onChange={(value) => {
                                setSelectedStatus(value);
                                applyFilter({ status: value });
                            }}
                        />
                    </div>
                    <div className="w-56">
                        <Label htmlFor="contract-company">Empresa</Label>
                        <SelectFilter
                            id="contract-company"
                            value={selectedCompany}
                            options={companies}
                            placeholder="Todas"
                            onChange={(value) => {
                                setSelectedCompany(value);
                                applyFilter({ company_id: value });
                            }}
                        />
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                        className="btn-taxi border-0"
                    >
                        Buscar
                    </Button>
                    {filters.q || filters.status || filters.company_id ? (
                        <Button asChild type="button" variant="ghost" size="sm">
                            <Link href={index()}>Limpiar</Link>
                        </Button>
                    ) : null}
                </form>

                <p className="tabular text-sm text-muted-foreground">
                    {contracts.total}{' '}
                    {contracts.total === 1 ? 'contrato' : 'contratos'}
                </p>

                {contracts.data.length === 0 ? (
                    <div className="neu-card p-8 text-center text-sm text-muted-foreground">
                        No hay contratos que coincidan con los filtros.
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
                            <table className="tabular w-full min-w-[58rem] text-sm">
                                <thead className="bg-muted/60 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Contrato
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Trabajador
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Empresa
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Vigencia
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Salario básico
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Estado
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Acciones
                                        </th>
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
                                                    {contract.code}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {contract.contract_type}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">
                                                    {contract.worker.name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {contract.worker.document}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div>{contract.company}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {contract.position}
                                                </div>
                                            </td>
                                            <td className="tabular px-4 py-3 text-xs">
                                                <div>
                                                    {day(contract.dates.start)}{' '}
                                                    →{' '}
                                                    {day(contract.dates.end) ??
                                                        'Abierto'}
                                                </div>
                                            </td>
                                            <td className="tabular px-4 py-3 text-right">
                                                {money(contract.salary.basic)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    className={
                                                        statusColor[
                                                            contract.status
                                                                .value
                                                        ]
                                                    }
                                                >
                                                    {contract.status.label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {contract.can_liquidate ? (
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        className="btn-taxi mr-2 border-0"
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
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={edit(contract.id)}
                                                    >
                                                        Editar
                                                    </Link>
                                                </Button>
                                                {contract.liquidations.length >
                                                0 ? (
                                                    <div className="mt-1 text-xs text-muted-foreground">
                                                        <Link
                                                            href={showLiquidation(
                                                                contract
                                                                    .liquidations[0]
                                                                    .id,
                                                            )}
                                                            className="underline"
                                                        >
                                                            {
                                                                contract
                                                                    .liquidations[0]
                                                                    .folio
                                                            }
                                                        </Link>
                                                    </div>
                                                ) : null}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}

                <Pager page={contracts} label="Paginación de contratos" />
            </div>
        </>
    );
}

function SelectFilter({
    id,
    value,
    options,
    placeholder,
    onChange,
}: {
    id: string;
    value: string;
    options: Option[] | NumberedOption[];
    placeholder: string;
    onChange: (value: string) => void;
}) {
    return (
        <select
            id={id}
            value={value}
            onChange={(event) => onChange(event.target.value)}
            className="mt-1 h-9 w-full rounded-xl border border-input bg-background px-2 text-sm"
            aria-label={placeholder}
        >
            <option value="">{placeholder}</option>
            {options.map((option) => (
                <option key={option.value} value={String(option.value)}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

function ContractSummary({ contract }: { contract: ContractCard }) {
    return (
        <article className="neu-card neu-enter p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-medium">
                        {contract.worker.name} · {contract.code}
                    </p>
                    <p className="tabular text-xs text-muted-foreground">
                        {contract.company}
                    </p>
                </div>
                <Badge className={statusColor[contract.status.value]}>
                    {contract.status.label}
                </Badge>
            </div>

            <dl className="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <div>{day(contract.dates.start)}</div>
                <div className="tabular text-right">
                    {money(contract.salary.basic)}
                </div>
                <div>
                    {count(
                        contract.liquidations.length,
                        'liquidación',
                        'liquidaciones',
                    )}
                </div>
                <div className="text-right">{contract.contract_type}</div>
            </dl>

            <div className="mt-4 flex flex-wrap gap-2">
                {contract.can_liquidate ? (
                    <Button
                        asChild
                        size="sm"
                        className="btn-taxi flex-1 border-0"
                    >
                        <Link href={liquidate(contract.id)}>Liquidar</Link>
                    </Button>
                ) : null}
                <Button asChild variant="outline" size="sm" className="flex-1">
                    <Link href={edit(contract.id)}>Editar</Link>
                </Button>
            </div>
        </article>
    );
}
