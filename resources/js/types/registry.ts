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
