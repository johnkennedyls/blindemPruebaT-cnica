import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Pager } from '@/components/pager';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { count, day } from '@/lib/format';
import { create, edit, index } from '@/routes/workers';
import type { Paginated, WorkerCard } from '@/types';

type Props = {
    workers: Paginated<WorkerCard>;
    filters: { q: string | null };
};

export default function WorkersIndex({ workers, filters }: Props) {
    const isAdmin = usePage().props.auth.is_admin;
    const [term, setTerm] = useState(filters.q ?? '');

    return (
        <>
            <Head title="Trabajadores" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Trabajadores"
                        description="Persona natural que firma el contrato y recibe la liquidación."
                    />
                    {isAdmin ? (
                        <Button asChild size="sm" className="btn-taxi border-0">
                            <Link href={create()}>Registrar trabajador</Link>
                        </Button>
                    ) : (
                        <p className="text-xs text-muted-foreground">
                            Solo lectura: quien registra trabajadores es el
                            administrador.
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
                        <Label htmlFor="worker-search">
                            Buscar por nombres, apellidos o documento
                        </Label>
                        <Input
                            id="worker-search"
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
                    {workers.total}{' '}
                    {workers.total === 1 ? 'trabajador' : 'trabajadores'}
                </p>

                {workers.data.length === 0 ? (
                    <div className="neu-card p-8 text-center text-sm text-muted-foreground">
                        No hay trabajadores que coincidan con la búsqueda.
                    </div>
                ) : (
                    <>
                        <div className="grid gap-3 md:hidden">
                            {workers.data.map((worker) => (
                                <WorkerSummary
                                    key={worker.id}
                                    worker={worker}
                                    isAdmin={isAdmin}
                                />
                            ))}
                        </div>

                        <div className="neu-card hidden overflow-x-auto md:block">
                            <table className="tabular w-full min-w-[52rem] text-sm">
                                <thead className="bg-muted/60 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Trabajador
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Documento
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Nacimiento
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Contacto
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Ciudad
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Contratos
                                        </th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {workers.data.map((worker) => (
                                        <tr
                                            key={worker.id}
                                            className="border-t border-border/60"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {worker.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                {worker.document}
                                            </td>
                                            <td className="px-4 py-3">
                                                {worker.birth_date
                                                    ? day(worker.birth_date)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                <div>{worker.phone ?? '—'}</div>
                                                <div className="text-muted-foreground">
                                                    {worker.email ?? '—'}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                {worker.city ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {count(
                                                    worker.contracts_count,
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
                                                                worker.id,
                                                            )}
                                                        >
                                                            Editar
                                                        </Link>
                                                    </Button>
                                                ) : (
                                                    <StatusChip
                                                        worker={worker}
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

                <Pager page={workers} label="Paginación de trabajadores" />
            </div>
        </>
    );
}

function StatusChip({ worker }: { worker: WorkerCard }) {
    return (
        <Badge variant={worker.is_active ? 'default' : 'secondary'}>
            {worker.is_active ? 'Activo' : 'Inactivo'}
        </Badge>
    );
}

function WorkerSummary({
    worker,
    isAdmin,
}: {
    worker: WorkerCard;
    isAdmin: boolean;
}) {
    return (
        <article className="neu-card neu-enter p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-medium">{worker.name}</p>
                    <p className="tabular text-xs text-muted-foreground">
                        {worker.document}
                    </p>
                </div>
                <StatusChip worker={worker} />
            </div>

            <dl className="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <div>{worker.city ?? 'Sin ciudad'}</div>
                <div className="text-right">
                    {count(worker.contracts_count, 'contrato', 'contratos')}
                </div>
                <div>{worker.phone ?? 'Sin teléfono'}</div>
                <div className="text-right">{worker.email ?? '—'}</div>
            </dl>

            {isAdmin ? (
                <Button
                    asChild
                    variant="outline"
                    size="sm"
                    className="mt-4 w-full"
                >
                    <Link href={edit(worker.id)}>Editar trabajador</Link>
                </Button>
            ) : null}
        </article>
    );
}
