export type PaginatedList<T> = {
    data: Array<T>;
    meta: PaginationMeta;
    links: PaginationLinks;
}

export type Pagination = {
    meta: PaginationMeta;
    links: PaginationLinks;
}

export type PaginationMeta = {
    path: string;
    per_page: number;
    total: number
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    links: Array<PaginationLink>;
}

export type PaginationLinks = {
    first: string;
    last: string;
    next: string | null;
    prev: string | null;
}

export type PaginationLink = {
    active: boolean;
    label: string;
    url: string | null;
}

export type LinkProps = {
    isPrev: boolean | undefined;
    isNext: boolean | undefined;
    isEllipsis: boolean;
    active?: boolean | undefined;
    label?: string | undefined;
    url?: string | null | undefined;
}
