import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { count } from '@/lib/format';
import { destroy, index, store, update } from '@/routes/companies';
import type { CompanyCard } from '@/types';

type FormData = {
    nit: string;
    business_name: string;
    trade_name: string;
    activity_code: string;
    address: string;
    city: string;
    phone: string;
    email: string;
    legal_rep_name: string;
    legal_rep_document: string;
    is_active: boolean;
};

type Props = {
    company: CompanyCard | null;
};

const blank: FormData = {
    nit: '',
    business_name: '',
    trade_name: '',
    activity_code: '',
    address: '',
    city: 'Cali',
    phone: '',
    email: '',
    legal_rep_name: '',
    legal_rep_document: '',
    is_active: true,
};

export default function CompanyForm({ company }: Props) {
    const isAdmin = usePage().props.auth.is_admin;
    const route = company ? update(company.id) : store();

    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        ...blank,
        ...(company
            ? {
                  nit: company.nit,
                  business_name: company.business_name,
                  trade_name: company.trade_name ?? '',
                  activity_code: company.activity_code ?? '',
                  address: company.address ?? '',
                  city: company.city ?? '',
                  phone: company.phone ?? '',
                  email: company.email ?? '',
                  legal_rep_name: company.legal_rep_name ?? '',
                  legal_rep_document: company.legal_rep_document ?? '',
                  is_active: company.is_active,
              }
            : null),
    });

    const field = (key: keyof FormData, value: string | boolean) =>
        setData((current) => ({ ...current, [key]: value }));

    return (
        <>
            <Head
                title={
                    company
                        ? `Editar ${company.business_name}`
                        : 'Registrar empresa'
                }
            />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={company ? 'Editar empresa' : 'Registrar empresa'}
                        description="El NIT y la razón social se imprimen en el encabezado de la liquidación."
                    />
                    <Button asChild variant="ghost" size="sm">
                        <Link href={index()}>Volver a la lista</Link>
                    </Button>
                </div>

                <form
                    className="neu-card space-y-5 p-5 md:p-7"
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (company) {
                            put(route.url, { preserveScroll: true });

                            return;
                        }

                        post(route.url, { preserveScroll: true });
                    }}
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Entry
                            id="nit"
                            label="NIT"
                            value={data.nit}
                            error={errors.nit}
                            onChange={(value) => field('nit', value)}
                            hint="Sin dígito de verificación si aún no lo conoce."
                        />
                        <Entry
                            id="business_name"
                            label="Razón social"
                            value={data.business_name}
                            error={errors.business_name}
                            onChange={(value) => field('business_name', value)}
                        />
                        <Entry
                            id="trade_name"
                            label="Nombre comercial"
                            value={data.trade_name}
                            error={errors.trade_name}
                            onChange={(value) => field('trade_name', value)}
                            optional
                        />
                        <Entry
                            id="activity_code"
                            label="Código de actividad"
                            value={data.activity_code}
                            error={errors.activity_code}
                            onChange={(value) => field('activity_code', value)}
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
                            id="phone"
                            label="Teléfono"
                            value={data.phone}
                            error={errors.phone}
                            onChange={(value) => field('phone', value)}
                            optional
                        />
                        <Entry
                            id="email"
                            label="Correo de notificaciones"
                            value={data.email}
                            error={errors.email}
                            onChange={(value) => field('email', value)}
                            optional
                        />
                        <Entry
                            id="address"
                            label="Dirección"
                            value={data.address}
                            error={errors.address}
                            onChange={(value) => field('address', value)}
                            optional
                        />
                        <Entry
                            id="legal_rep_name"
                            label="Representante legal"
                            value={data.legal_rep_name}
                            error={errors.legal_rep_name}
                            onChange={(value) => field('legal_rep_name', value)}
                            optional
                        />
                        <Entry
                            id="legal_rep_document"
                            label="Documento del representante"
                            value={data.legal_rep_document}
                            error={errors.legal_rep_document}
                            onChange={(value) =>
                                field('legal_rep_document', value)
                            }
                            optional
                        />
                    </div>

                    <label className="flex items-center gap-3 text-sm">
                        <Checkbox
                            checked={data.is_active}
                            onCheckedChange={(checked) =>
                                field('is_active', checked === true)
                            }
                        />
                        La empresa sigue activa para registrar contratos
                    </label>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button
                            type="submit"
                            className="btn-taxi border-0"
                            disabled={processing}
                        >
                            {company ? 'Guardar cambios' : 'Registrar empresa'}
                        </Button>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={index()}>Cancelar</Link>
                        </Button>
                    </div>
                </form>

                {company && isAdmin ? (
                    <div className="neu-card flex flex-wrap items-center justify-between gap-4 border-l-4 border-l-destructive/70 p-5">
                        <div>
                            <p className="text-sm font-medium">
                                Eliminar esta empresa
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {company.contracts_count
                                    ? `${count(company.contracts_count, 'contrato', 'contratos')} registrado${company.contracts_count === 1 ? '' : 's'} con esta empresa; mientras existan, no se podrá eliminar.`
                                    : 'Todavía no tiene contratos registrados.'}
                            </p>
                        </div>
                        <ConfirmDeleteDialog
                            action={destroy(company.id).url}
                            label="Eliminar empresa"
                            question={`¿Eliminar a ${company.business_name}?`}
                            description="La eliminación es definitiva. Si la empresa siguió operando, desactívela en lugar de borrarla."
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
    hint,
    optional,
}: {
    id: string;
    label: string;
    value: string;
    error?: string;
    onChange: (value: string) => void;
    hint?: string;
    optional?: boolean;
}) {
    return (
        <div>
            <Label htmlFor={id}>
                {label}
                {optional ? ' (opcional)' : ''}
            </Label>
            <Input
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-1"
            />
            {hint ? (
                <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
            ) : null}
            <InputError message={error} className="mt-1" />
        </div>
    );
}
