export type ProposalRow = {
    id: number;
    number: string;
    title: string;
    status: 'draft' | 'sent' | 'viewed' | 'changes_requested' | 'accepted' | 'rejected' | 'expired';
    status_label: string;
    status_color: string;
    currency: 'UF' | 'CLP';
    uf_value: number | null;
    uf_date: string | null;
    total_gross_clp: number;
    company: string | null;
    contact: string | null;
    client: { id: number; name: string } | null;
    lead: { id: number; name: string } | null;
    owner: string | null;
    total_net: number;
    total_gross: number;
    total_monthly: number;
    total_one_time: number;
    valid_until: string | null;
    issued_at: string | null;
    created_at: string;
};

export type Recipient = {
    company?: string | null;
    legal_name?: string | null;
    rut?: string | null;
    activity?: string | null;
    address?: string | null;
    contact_name?: string | null;
    contact_role?: string | null;
    email?: string | null;
    phone?: string | null;
};

export type ProposalItemInput = {
    key: string;
    service_id: number | null;
    name: string;
    description: string;
    deliverables: string[];
    billing: 'one_time' | 'monthly';
    unit: string;
    quantity: number;
    unit_price: number;
    discount_pct: number;
};

export type CatalogService = { id: number; name: string; category: string; description: string | null; deliverables: string[] | null; billing: 'one_time' | 'monthly'; unit: string; currency: 'UF' | 'CLP'; price: number };
