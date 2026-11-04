import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Pager } from '@/components/pager';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { count } from '@/lib/format';
import { create, edit, index } from '@/routes/parametrics';
import type {
    Paginated,
    ParametricDefinitionData,
    ParametricRow,
} from '@/types';

type Props = {
    definition: ParametricDefinitionData;
    rows: Paginated<ParametricRow>;
    filters: { q: string | null };
};

export default function ParametricsIndex({ definition, rows, filters }: Props) {
    const { parametrics } = usePage().props;
    const [term, setTerm] = useState(filters.q ?? '');
    const baseUrl = index({ resource: definition.slug }).url;

    return (
        <>
            <Head title={definition.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={definition.title}
                        description={definition.description}
                    />
                    <Button asChild size="sm" className="btn-taxi border-0">
                        <Link href={create({ resource: definition.slug })}>
                            Registrar {definition.one}
                        </Link>
                    </Button>
                </div>

                <nav
                    aria-label="Catálogos paramétricos"
                    className="flex flex-wrap gap-2"
                >
                    {parametrics.map((catalog) => (
                        <Link
                            key={catalog.slug}
                            href={index({ resource: catalog.slug }).url}
                            className={
                                catalog.slug === definition.slug
                                    ? 'btn-taxi rounded-full px-3.5 py-1.5 text-sm'
                                    : 'neu-sunken rounded-full px-3.5 py-1.5 text-sm text-muted-foreground'
                            }
                        >
                            {catalog.title}
                        </Link>
                    ))}
                </nav>

                <form
                    className="neu-card flex flex-wrap items-end gap-3 p-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(baseUrl, term ? { q: term } : {});
                    }}
                >
                    <div className="min-w-56 flex-1">
                        <Label htmlFor="parametric-search">
                            Buscar por {definition.headline_label.toLowerCase()}{' '}
                            o nombre
                        </Label>
                        <Input
                            id="parametric-search"
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
                            <Link href={baseUrl}>Limpiar</Link>
                        </Button>
                    ) : null}
                </form>

                <p className="tabular text-sm text-muted-foreground">
                    {rows.total}{' '}
                    {rows.total === 1
                        ? definition.one
                        : definition.title.toLowerCase()}
                </p>

                {rows.data.length === 0 ? (
                    <div className="neu-card p-8 text-center text-sm text-muted-foreground">
                        No hay registros que coincidan con la búsqueda.
                    </div>
                ) : (
                    <>
                        <div className="grid gap-3 md:hidden">
                            {rows.data.map((row) => (
                                <RowCard
                                    key={row.id}
                                    row={row}
                                    definition={definition}
                                />
                            ))}
                        </div>

                        <div className="neu-card hidden overflow-x-auto md:block">
                            <table className="tabular w-full min-w-[48rem] text-sm">
                                <thead className="bg-muted/60 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            {definition.headline_label}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Nombre
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Detalle
                                        </th>
                                        {definition.usage_label ? (
                                            <th className="px-4 py-3 text-right font-medium">
                                                Uso
                                            </th>
                                        ) : null}
                                        {definition.has_pause_state ? (
                                            <th className="px-4 py-3 font-medium">
                                                Estado
                                            </th>
                                        ) : null}
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-t border-border/60"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {row.headline}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.primary}
                                            </td>
                                            <td className="px-4 py-3 text-xs text-muted-foreground">
                                                {row.secondary ?? '—'}
                                            </td>
                                            {definition.usage_label ? (
                                                <td className="px-4 py-3 text-right">
                                                    {row.usage_count === null
                                                        ? '—'
                                                        : count(
                                                              row.usage_count,
                                                              definition.usage_label,
                                                          )}
                                                </td>
                                            ) : null}
                                            {definition.has_pause_state ? (
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={
                                                            row.is_active
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {row.is_active
                                                            ? 'Activo'
                                                            : 'Inactivo'}
                                                    </Badge>
                                                </td>
                                            ) : null}
                                            <td className="px-4 py-3 text-right">
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={edit({
                                                            resource:
                                                                definition.slug,
                                                            parametric: row.id,
                                                        })}
                                                    >
                                                        {row.primary}
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}

                <Pager
                    page={rows}
                    label={`Paginación de ${definition.title}`}
                />
            </div>
        </>
    );
}

function RowCard({
    row,
    definition,
}: {
    row: ParametricRow;
    definition: ParametricDefinitionData;
}) {
    return (
        <article className="neu-card neu-enter p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-medium">
                        {row.headline} · {row.primary}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {row.secondary ?? '—'}
                    </p>
                </div>
                {definition.has_pause_state ? (
                    <Badge variant={row.is_active ? 'default' : 'secondary'}>
                        {row.is_active ? 'Activo' : 'Inactivo'}
                    </Badge>
                ) : null}
            </div>
            {row.usage_count !== null && definition.usage_label ? (
                <p className="mt-3 text-xs text-muted-foreground">
                    {count(row.usage_count, definition.usage_label)}
                </p>
            ) : null}
            <Button asChild variant="outline" size="sm" className="mt-4 w-full">
                <Link
                    href={edit({
                        resource: definition.slug,
                        parametric: row.id,
                    })}
                >
                    Editar {definition.one}
                </Link>
            </Button>
        </article>
    );
}
