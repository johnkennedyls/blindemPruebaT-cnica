import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Pager } from '@/components/pager';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { count } from '@/lib/format';
import { create, edit, index } from '@/routes/companies';
import type { CompanyCard, Paginated } from '@/types';

type Props = {
    companies: Paginated<CompanyCard>;
    filters: { q: string | null };
};

export default function CompaniesIndex({ companies, filters }: Props) {
    const isAdmin = usePage().props.auth.is_admin;
    const [term, setTerm] = useState(filters.q ?? '');

    return (
        <>
            <Head title="Empresas" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Empresas"
                        description="Datos del empleador que aparecen en el encabezado de cada liquidación."
                    />
                    {isAdmin ? (
                        <Button asChild size="sm" className="btn-taxi border-0">
                            <Link href={create()}>Registrar empresa</Link>
                        </Button>
                    ) : (
                        <p className="text-xs text-muted-foreground">
                            Solo lectura: quien registra empresas es el
                            administrador. Usted aparece en este listado porque
                            colabora en ellas.
                        </p>
                    )}
                </div>

                <form
                    className="neu-card flex flex-wrap items-end gap-3 p-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(index().url, term ? { q: term } : {});
                    }}
                >
                    <div className="min-w-56 flex-1">
                        <Label htmlFor="company-search">
                            Buscar por razón social, nombre comercial o NIT
                        </Label>
                        <Input
                            id="company-search"
                            value={term}
                            onChange={(event) => setTerm(event.target.value)}
                            className="mt-1"
                        />
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                        className="btn-taxi border-0"
                    >
                        Buscar
                    </Button>
                    {filters.q ? (
                        <Button asChild type="button" variant="ghost" size="sm">
                            <Link href={index()}>Limpiar</Link>
                        </Button>
                    ) : null}
                </form>

                <p className="tabular text-sm text-muted-foreground">
                    {companies.total}{' '}
                    {companies.total === 1 ? 'empresa' : 'empresas'}
                </p>

                {companies.data.length === 0 ? (
                    <div className="neu-card p-8 text-center text-sm text-muted-foreground">
                        No hay empresas que coincidan con la búsqueda.
                    </div>
                ) : (
                    <>
                        <div className="grid gap-3 md:hidden">
                            {companies.data.map((company) => (
                                <CompanySummary
                                    key={company.id}
                                    company={company}
                                    isAdmin={isAdmin}
                                />
                            ))}
                        </div>

                        <div className="neu-card hidden overflow-x-auto md:block">
                            <table className="tabular w-full min-w-[52rem] text-sm">
                                <thead className="bg-muted/60 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Razón social
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            NIT
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Ciudad
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Contacto
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Representante
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Contratos
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {companies.data.map((company) => (
                                        <tr
                                            key={company.id}
                                            className="border-t border-border/60"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="font-medium">
                                                    {company.business_name}
                                                </div>
                                                {company.trade_name ? (
                                                    <div className="text-xs text-muted-foreground">
                                                        {company.trade_name}
                                                    </div>
                                                ) : null}
                                            </td>
                                            <td className="px-4 py-3">
                                                {company.nit}
                                            </td>
                                            <td className="px-4 py-3">
                                                {company.city ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                <div>
                                                    {company.phone ?? '—'}
                                                </div>
                                                <div className="text-muted-foreground">
                                                    {company.email ?? '—'}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                <div>
                                                    {company.legal_rep_name ??
                                                        '—'}
                                                </div>
                                                <div className="text-muted-foreground">
                                                    {company.legal_rep_document ??
                                                        '—'}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {count(
                                                    company.contracts_count,
                                                    'contrato',
                                                    'contratos',
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {isAdmin ? (
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        <Link
                                                            href={edit(
                                                                company.id,
                                                            )}
                                                        >
                                                            Editar
                                                        </Link>
                                                    </Button>
                                                ) : (
                                                    <StatusChip
                                                        company={company}
                                                    />
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}

                <Pager page={companies} label="Paginación de empresas" />
            </div>
        </>
    );
}

function StatusChip({ company }: { company: CompanyCard }) {
    return (
        <Badge variant={company.is_active ? 'default' : 'secondary'}>
            {company.is_active ? 'Activa' : 'Inactiva'}
        </Badge>
    );
}

function CompanySummary({
    company,
    isAdmin,
}: {
    company: CompanyCard;
    isAdmin: boolean;
}) {
    return (
        <article className="neu-card neu-enter p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-medium">{company.business_name}</p>
                    <p className="tabular text-xs text-muted-foreground">
                        NIT {company.nit}
                    </p>
                </div>
                <StatusChip company={company} />
            </div>

            <dl className="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <div>{company.city ?? 'Sin ciudad'}</div>
                <div className="text-right">
                    {count(company.contracts_count, 'contrato', 'contratos')}
                </div>
                <div>{company.phone ?? 'Sin teléfono'}</div>
                <div className="text-right">{company.email ?? '—'}</div>
            </dl>

            {isAdmin ? (
                <Button
                    asChild
                    variant="outline"
                    size="sm"
                    className="mt-4 w-full"
                >
                    <Link href={edit(company.id)}>Editar empresa</Link>
                </Button>
            ) : null}
        </article>
    );
}
