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
