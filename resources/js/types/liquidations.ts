export type Option = {
    value: string;
    label: string;
};

export type Paginated<T> = {
    current_page: number;
    data: T[];
    from: number | null;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
    next_page_url: string | null;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

export type ContractCard = {
    id: number;
    code: string;
    status: Option;
    can_liquidate: boolean;
    worker: {
        id: number;
        name: string;
        document: string;
        document_type_id: number;
        document_number: string;
        email: string | null;
    };
    company: string;
    position: string | null;
    contract_type: string | null;
    modality: string | null;
    termination_reason: string | null;
    applies_indemnization: boolean;
    dates: {
        start: string;
        end: string | null;
        scheduled_end: string | null;
    };
    salary: {
        basic: string;
        auxiliary: string;
        overtime_average: string;
    };
    pending_days: {
        vacations: number;
        salary: number;
    };
    liquidations: {
        id: number;
        folio: string;
        net: string;
        status: string;
        settlement_date: string;
    }[];
};

export type LiquidationLine = {
    code: string;
    name: string;
    side: Option;
    base: string;
    quantity: string;
    divisor: number | null;
    formula: string;
    trace: string | null;
    amount: string;
    accrues_social_security: boolean;
};

export type LiquidationDocument = {
    id: number;
    folio: string;
    status: Option;
    variant: Option;
    observations: string | null;
    creator: string | null;
    dates: {
        settlement: string;
        period_from: string | null;
        period_to: string | null;
    };
    bases: {
        prestacional: string;
        vacacional: string;
        smlmv: string;
    };
    days: {
        cesantias: number;
        prima: number;
        vacaciones: number;
        salario_pendiente: number;
        worked: number;
    };
    lines: LiquidationLine[];
    totals: {
        earned: string;
        deducted: string;
        net: string;
    };
    notices: string[];
    indemnization: {
        regla: string;
        formula: string;
        detalle: string;
    } | null;
    tenure: {
        anos_completos: number;
        fraccion: number;
        dias_del_ano: number;
    } | null;
    wages: {
        year: number;
        smlmv: string;
        transport_auxiliary: string;
    } | null;
    parameters: Record<string, string>;
    captured_at: string | null;
};
