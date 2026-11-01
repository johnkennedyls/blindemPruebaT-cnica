import type { Option } from './shared';

export type CompanyCard = {
    id: number;
    nit: string;
    business_name: string;
    trade_name: string | null;
    activity_code: string | null;
    address: string | null;
    city: string | null;
    phone: string | null;
    email: string | null;
    legal_rep_name: string | null;
    legal_rep_document: string | null;
    is_active: boolean;
    contracts_count: number | null;
};

export type WorkerCard = {
    id: number;
    name: string;
    document: string;
    first_names: string;
    last_names: string;
    document_type_id: number;
    document_number: string;
    birth_date: string | null;
    gender: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    department: string | null;
    is_active: boolean;
    document_type: string | null;
    contracts_count: number | null;
};

export type NumberedOption = {
    value: number;
    label: string;
};

export type ContractForm = {
    id: number;
    code: string;
    worker_id: number;
    worker_name: string;
    company_id: number;
    contract_type_id: number;
    position_id: number;
    termination_reason_id: number | null;
    creator: string | null;
    dates: {
        start: string;
        end: string | null;
        scheduled_end: string | null;
        salary_effective_from: string | null;
    };
    base_salary_monthly: string;
    transport_auxiliary_mode: string;
    transport_auxiliary_monthly: string;
    overtime_average_monthly: string;
    status: string;
    pending_salary_days: number;
    pending_vacation_days: number;
    notes: string | null;
    liquidations_count: number;
};

export type ParametricFieldData = {
    name: string;
    label: string;
    kind: 'text' | 'number' | 'select' | 'checkbox';
    required: boolean;
    options: Option[] | null;
    hint: string | null;
    max_length: number | null;
    default: string | boolean | number | null;
};

export type ParametricDefinitionData = {
    slug: string;
    title: string;
    one: string;
    description: string;
    headline: string;
    headline_label: string;
    has_pause_state: boolean;
    usage_label: string | null;
    fields: ParametricFieldData[];
};

export type ParametricRow = {
    id: number;
    headline: string;
    primary: string;
    secondary: string | null;
    is_active: boolean | null;
    usage_count: number | null;
};
