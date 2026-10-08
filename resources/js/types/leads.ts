export type SourceRef = { id: number; name: string; color: string; icon: string };
export type StageRef = { id: number; name: string; color: string; type?: string };

export type LeadCard = {
    id: number;
    full_name: string;
    company: string | null;
    job_title: string | null;
    email: string | null;
    phone: string | null;
    stage_id: number;
    position: number;
    utm_campaign: string | null;
    source: SourceRef | null;
    assignee: { id: number; name: string } | null;
    custom: { label: string; value: string }[];
    created_at: string;
    stage?: StageRef;
};

export type BoardColumn = {
    id: number;
    name: string;
    color: string;
    type: 'open' | 'won' | 'lost';
    total: number;
    leads: LeadCard[];
};

export type CustomFieldDef = {
    id: number;
    key: string;
    label: string;
    type: 'text' | 'textarea' | 'number' | 'email' | 'phone' | 'url' | 'date' | 'select' | 'checkbox';
    options: string[] | null;
    is_required: boolean;
};

export type UserOption = { id: number; name: string };
