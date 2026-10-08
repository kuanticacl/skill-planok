export type VarMeta = { label: string; default: string; sample: string };

export type TemplateRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    category: 'marketing' | 'transactional';
    subject: string;
    editor: 'blocks' | 'html';
    is_active: boolean;
    variables: string[];
    campaigns_count: number;
    sent_count: number;
    updated_at: string;
};
