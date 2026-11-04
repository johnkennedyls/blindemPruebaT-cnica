const moneyFormat = new Intl.NumberFormat('es-CO', {
    maximumFractionDigits: 0,
});
const quantityFormat = new Intl.NumberFormat('es-CO', {
    maximumFractionDigits: 6,
});

export function money(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return `$ ${moneyFormat.format(Math.round(Number(value)))}`;
}

export function quantity(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return quantityFormat.format(Number(value));
}

function pluralize(singular: string): string {
    return /[aeiou]$/i.test(singular) ? `${singular}s` : `${singular}es`;
}

export function count(
    value: string | number | null | undefined,
    singular: string,
    plural = pluralize(singular),
): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const number = Number(value);

    return `${quantityFormat.format(number)} ${number === 1 ? singular : plural}`;
}

export function day(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const [year, month, part] = value.split('-');

    if (!year || !month || !part) {
        return value;
    }

    return `${part}/${month}/${year}`;
}
