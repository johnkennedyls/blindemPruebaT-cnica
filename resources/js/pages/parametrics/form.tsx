import { Head, Link, useForm } from '@inertiajs/react';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { destroy, index, store, update } from '@/routes/parametrics';
import type { ParametricDefinitionData, ParametricFieldData } from '@/types';

type Props = {
    definition: ParametricDefinitionData;
    row: {
        id: number;
        headline: string;
        values: Record<string, string | boolean>;
    } | null;
};

type Value = string | boolean;

export default function ParametricForm({ definition, row }: Props) {
    const route = row
        ? update({ resource: definition.slug, parametric: row.id })
        : store({ resource: definition.slug });

    const initials: Record<string, Value> = {};
    for (const field of definition.fields) {
        const fromRow = row?.values[field.name];

        if (field.kind === 'checkbox') {
            initials[field.name] = fromRow === true || fromRow === '1';
            continue;
        }

        const fallback =
            typeof field.default === 'number'
                ? String(field.default)
                : (field.default ?? '');

        initials[field.name] = fromRow ?? fallback;
    }

    if (definition.has_pause_state) {
        initials.is_active =
            typeof row?.values.is_active === 'boolean'
                ? row.values.is_active
                : true;
    }

    const { data, setData, post, put, processing, errors } =
        useForm<Record<string, Value>>(initials);

    const set = (key: string, value: Value) =>
        setData((current) => ({ ...current, [key]: value }));

    return (
        <>
            <Head title={row ? `Editar ${definition.one}` : definition.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={
                            row
                                ? `Editar ${definition.one}`
                                : `Registrar ${definition.one}`
                        }
                        description={definition.description}
                    />
                    <Button asChild variant="ghost" size="sm">
                        <Link href={index({ resource: definition.slug })}>
                            Volver a la lista
                        </Link>
                    </Button>
                </div>

                <form
                    className="neu-card space-y-5 p-5 md:p-7"
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (row) {
                            put(route.url, { preserveScroll: true });

                            return;
                        }

                        post(route.url, { preserveScroll: true });
                    }}
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        {definition.fields.map((field) => (
                            <FieldInput
                                key={field.name}
                                field={field}
                                value={data[field.name]}
                                error={errors[field.name]}
                                onChange={(value) => set(field.name, value)}
                                wide={field.kind === 'checkbox'}
                            />
                        ))}
                    </div>

                    {definition.has_pause_state ? (
                        <label className="flex items-center gap-3 text-sm">
                            <Checkbox
                                checked={data.is_active === true}
                                onCheckedChange={(checked) =>
                                    set('is_active', checked === true)
                                }
                            />
                            Queda disponible para los formularios y filtros de
                            liquidación
                        </label>
                    ) : null}

                    <div className="flex flex-wrap items-center gap-3">
                        <Button
                            type="submit"
                            className="btn-taxi border-0"
                            disabled={processing}
                        >
                            {row
                                ? 'Guardar cambios'
                                : `Registrar ${definition.one}`}
                        </Button>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={index({ resource: definition.slug })}>
                                Cancelar
                            </Link>
                        </Button>
                    </div>
                </form>

                {row ? (
                    <div className="neu-card flex flex-wrap items-center justify-between gap-4 border-l-4 border-l-destructive/70 p-5">
                        <div>
                            <p className="text-sm font-medium">
                                Eliminar este {definition.one}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {definition.usage_label
                                    ? `Si hay ${definition.usage_label}s que lo referencian, el sistema no permitirá eliminarlo; desactívelo en ese caso.`
                                    : 'La eliminación es definitiva.'}
                            </p>
                        </div>
                        <ConfirmDeleteDialog
                            action={
                                destroy({
                                    resource: definition.slug,
                                    parametric: row.id,
                                }).url
                            }
                            label={`Eliminar ${definition.one}`}
                            question={`¿Eliminar "${row.headline}"?`}
                            description="No se podrá recuperar. Considere desactivar el registro en lugar de eliminarlo."
                        />
                    </div>
                ) : null}
            </div>
        </>
    );
}

function FieldInput({
    field,
    value,
    error,
    onChange,
    wide,
}: {
    field: ParametricFieldData;
    value: Value;
    error?: string;
    onChange: (value: Value) => void;
    wide?: boolean;
}) {
    return (
        <div className={wide ? 'sm:col-span-2' : undefined}>
            {field.kind === 'checkbox' ? null : (
                <Label htmlFor={field.name}>{field.label}</Label>
            )}
            {field.kind === 'checkbox' ? (
                <label className="mt-1 flex items-center gap-3 text-sm">
                    <Checkbox
                        checked={value === true}
                        onCheckedChange={(checked) =>
                            onChange(checked === true)
                        }
                    />
                    {field.label}
                </label>
            ) : field.kind === 'select' ? (
                <Select
                    value={String(value ?? '')}
                    onValueChange={(selected) => onChange(selected)}
                >
                    <SelectTrigger id={field.name} className="mt-1 w-full">
                        <SelectValue placeholder="Seleccionar" />
                    </SelectTrigger>
                    <SelectContent>
                        {(field.options ?? []).map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ) : (
                <Input
                    id={field.name}
                    type={field.kind === 'number' ? 'number' : 'text'}
                    value={String(value ?? '')}
                    onChange={(event) => onChange(event.target.value)}
                    className="mt-1"
                />
            )}
            {field.hint ? (
                <p className="mt-1 text-xs text-muted-foreground">
                    {field.hint}
                </p>
            ) : null}
            <InputError message={error} className="mt-1" />
        </div>
    );
}
