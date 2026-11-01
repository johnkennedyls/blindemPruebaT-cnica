import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { count } from '@/lib/format';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { destroy, index, store, update } from '@/routes/workers';
import type { NumberedOption, Option, WorkerCard } from '@/types';

type FormData = {
    document_type_id: string;
    document_number: string;
    first_names: string;
    last_names: string;
    birth_date: string;
    gender: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    department: string;
    is_active: boolean;
};

type Props = {
    worker: WorkerCard | null;
    documentTypes: NumberedOption[];
    genders: Option[];
};

const none = 'sin_genero';

export default function WorkerForm({ worker, documentTypes, genders }: Props) {
    const isAdmin = usePage().props.auth.is_admin;
    const route = worker ? update(worker.id) : store();

    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        document_type_id: String(
            worker?.document_type_id ?? documentTypes[0]?.value ?? '',
        ),
        document_number: worker?.document_number ?? '',
        first_names: worker?.first_names ?? '',
        last_names: worker?.last_names ?? '',
        birth_date: worker?.birth_date ?? '',
        gender: worker?.gender ?? '',
        email: worker?.email ?? '',
        phone: worker?.phone ?? '',
        address: worker?.address ?? '',
        city: worker?.city ?? '',
        department: worker?.department ?? '',
        is_active: worker?.is_active ?? true,
    });

    const field = (key: keyof FormData, value: string | boolean) =>
        setData((current) => ({ ...current, [key]: value }));

    return (
        <>
            <Head
                title={
                    worker ? `Editar ${worker.name}` : 'Registrar trabajador'
                }
            />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={
                            worker
                                ? 'Editar trabajador'
                                : 'Registrar trabajador'
                        }
                        description="El tipo y el número de documento identifican al trabajador en los filtros y en la liquidación."
                    />
                    <Button asChild variant="ghost" size="sm">
                        <Link href={index()}>Volver a la lista</Link>
                    </Button>
                </div>

                <form
                    className="neu-card space-y-5 p-5 md:p-7"
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (worker) {
                            put(route.url, { preserveScroll: true });

                            return;
                        }

                        post(route.url, { preserveScroll: true });
                    }}
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="document_type_id">
                                Tipo de documento
                            </Label>
                            <Select
                                value={data.document_type_id}
                                onValueChange={(value) =>
                                    field('document_type_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="document_type_id"
                                    className="mt-1 w-full"
                                >
                                    <SelectValue placeholder="Tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    {documentTypes.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={String(type.value)}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={errors.document_type_id}
                                className="mt-1"
                            />
                        </div>

                        <Entry
                            id="document_number"
                            label="Número de documento"
                            value={data.document_number}
                            error={errors.document_number}
                            onChange={(value) =>
                                field('document_number', value)
                            }
                        />
                        <Entry
                            id="first_names"
                            label="Nombres"
                            value={data.first_names}
                            error={errors.first_names}
                            onChange={(value) => field('first_names', value)}
                        />
                        <Entry
                            id="last_names"
                            label="Apellidos"
                            value={data.last_names}
                            error={errors.last_names}
                            onChange={(value) => field('last_names', value)}
                        />
                        <Entry
                            id="birth_date"
                            label="Fecha de nacimiento"
                            type="date"
                            value={data.birth_date}
                            error={errors.birth_date}
                            onChange={(value) => field('birth_date', value)}
                            optional
                        />
                        <div>
                            <Label htmlFor="gender">Sexo</Label>
                            <Select
                                value={data.gender}
                                onValueChange={(value) =>
                                    field('gender', value === none ? '' : value)
                                }
                            >
                                <SelectTrigger
                                    id="gender"
                                    className="mt-1 w-full"
                                >
                                    <SelectValue placeholder="No registrado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={none}>
                                        No registrado
                                    </SelectItem>
                                    {genders.map((gender) => (
                                        <SelectItem
                                            key={gender.value}
                                            value={gender.value}
                                        >
                                            {gender.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={errors.gender}
                                className="mt-1"
                            />
                        </div>
                        <Entry
                            id="email"
                            label="Correo"
                            value={data.email}
                            error={errors.email}
                            onChange={(value) => field('email', value)}
                            optional
                        />
                        <Entry
                            id="phone"
                            label="Teléfono"
                            value={data.phone}
                            error={errors.phone}
                            onChange={(value) => field('phone', value)}
                            optional
                        />
                        <Entry
                            id="city"
                            label="Ciudad"
                            value={data.city}
                            error={errors.city}
                            onChange={(value) => field('city', value)}
                            optional
                        />
                        <Entry
                            id="department"
                            label="Departamento"
                            value={data.department}
                            error={errors.department}
                            onChange={(value) => field('department', value)}
                            optional
                        />
                        <div className="sm:col-span-2">
                            <Entry
                                id="address"
                                label="Dirección"
                                value={data.address}
                                error={errors.address}
                                onChange={(value) => field('address', value)}
                                optional
                            />
                        </div>
                    </div>

                    <label className="flex items-center gap-3 text-sm">
                        <Checkbox
                            checked={data.is_active}
                            onCheckedChange={(checked) =>
                                field('is_active', checked === true)
                            }
                        />
                        El trabajador sigue activo para registrar contratos
                    </label>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button
                            type="submit"
                            className="btn-taxi border-0"
                            disabled={processing}
                        >
                            {worker
                                ? 'Guardar cambios'
                                : 'Registrar trabajador'}
                        </Button>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={index()}>Cancelar</Link>
                        </Button>
                    </div>
                </form>

                {worker && isAdmin ? (
                    <div className="neu-card flex flex-wrap items-center justify-between gap-4 border-l-4 border-l-destructive/70 p-5">
                        <div>
                            <p className="text-sm font-medium">
                                Eliminar este trabajador
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {worker.contracts_count
                                    ? `${count(worker.contracts_count, 'contrato', 'contratos')} registrado${worker.contracts_count === 1 ? '' : 's'} a nombre de ${worker.name}; mientras existan, no se podrá eliminar.`
                                    : 'Todavía no tiene contratos registrados.'}
                            </p>
                        </div>
                        <ConfirmDeleteDialog
                            action={destroy(worker.id).url}
                            label="Eliminar trabajador"
                            question={`¿Eliminar a ${worker.name}?`}
                            description="La eliminación es definitiva y borra el historial del trabajador en Blindem. Si se retiró, desactívelo en lugar de borrarlo."
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
    optional,
    type = 'text',
}: {
    id: string;
    label: string;
    value: string;
    error?: string;
    onChange: (value: string) => void;
    optional?: boolean;
    type?: string;
}) {
    return (
        <div>
            <Label htmlFor={id}>
                {label}
                {optional ? ' (opcional)' : ''}
            </Label>
            <Input
                id={id}
                type={type}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-1"
            />
            <InputError message={error} className="mt-1" />
        </div>
    );
}
