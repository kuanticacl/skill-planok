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
    priority: 'low' | 'normal' | 'high' | 'urgent';
    estimated_value: number | null;
    tags: string[];
    next_follow_up_at: string | null;
    stage_changed_at: string;
    closed_at: string | null;
    lost_reason: string | null;
    last_activity_at: string | null;
    created_at: string;
    stage?: StageRef;
};

export type BoardColumn = {
    id: number;
    name: string;
    color: string;
    type: 'open' | 'won' | 'lost';
    total: number;
    value: number;
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

export type LeadDetail = LeadCard & {
    first_name: string;
    last_name: string | null;
    message: string | null;
    client: { id: number; name: string } | null;
    stage: StageRef & { type: 'open' | 'won' | 'lost' };
    utm: Record<string, string | null>;
    capture: Record<string, string | number | null>;
    meta: Record<string, unknown> | null;
};
export type LeadNote = { id: number; body: string; is_private: boolean; author: string; mine: boolean; created_at: string };
export type LeadActivityItem = { id: number; type: string; description: string | null; user: string | null; occurred_at: string };

export type PanelData = {
    lead: LeadDetail;
    customFields: { key: string; label: string; type: string; value: unknown; active: boolean }[];
    notes: LeadNote[];
    activities: LeadActivityItem[];
    stages: (StageRef & { type: 'open' | 'won' | 'lost' })[];
    users: UserOption[];
    priorities: Record<string, string>;
    followUpTypes: Record<string, string>;
    can: { update: boolean; move: boolean; delete: boolean; note: boolean; assign: boolean };
};

