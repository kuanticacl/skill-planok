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
    score: number | null;
    score_grade: 'A' | 'B' | 'C' | 'D' | null;
    profile_completeness: number | null;
    estimated_value: number | null;
    estimated_currency: 'CLP' | 'UF';
    estimated_amount: number | null;
    tags: string[];
    next_follow_up_at: string | null;
    stage_changed_at: string;
    closed_at: string | null;
    lost_reason: string | null;
    last_activity_at: string | null;
    created_at: string;
    stage?: StageRef;
    proposal?: { id: number; number: string; status: string; status_label: string; status_color: string; currency: 'UF' | 'CLP'; total_net: number; count: number } | null;
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
export type LeadNote = { id: number; body: string; body_html: string; is_private: boolean; author: string; mine: boolean; created_at: string };
export type LeadActivityItem = { id: number; type: string; description: string | null; user: string | null; occurred_at: string };

export type ScoreItem = { label: string; points: number; max: number; ok: boolean; hint: string | null };
export type ScoreGroup = { key: string; label: string; points: number; max: number; items: ScoreItem[] };
export type Scoring = {
    score: number;
    grade: 'A' | 'B' | 'C' | 'D';
    grade_label: string;
    completeness: number;
    breakdown: { groups: ScoreGroup[]; base: number; ai_adjustment: number; note: string | null };
    missing: { field: string; label: string; why: string; impact: number }[];
    signals: { key: string; label: string; value: string }[];
    scored_at: string | null;
};
export type AiAnalysis = {
    summary: string;
    temperature: 'hot' | 'warm' | 'cold';
    score_adjustment: number;
    adjustment_reason: string;
    buying_signals: string[];
    risks: string[];
    next_actions: { action: string; channel: 'call' | 'email' | 'whatsapp' | 'meeting' | 'task'; when: 'today' | 'this_week' | 'later'; why: string }[];
    suggested_message: string;
    questions_to_ask: string[];
    profile_suggestions: { field: 'priority' | 'tags' | 'company' | 'job_title'; value: string; confidence: 'high' | 'medium' | 'low'; reason: string }[];
    provider: string;
    model: string | null;
    generated_at: string;
    shared_contact: boolean;
};
export type AiPanel = { can_use: boolean; available: boolean; can_configure: boolean; analysis: AiAnalysis | null; analyzed_at: string | null; stale: boolean };

export type LeadProposal = { id: number; number: string; title: string; status: string; status_label: string; status_color: string; currency: 'UF' | 'CLP'; total_net: number; total_gross: number; valid_until: string | null; created_at: string };

export type PanelData = {
    proposals: LeadProposal[];
    scoring: Scoring;
    ai: AiPanel;
    lead: LeadDetail;
    customFields: { key: string; label: string; type: string; value: unknown; active: boolean }[];
    notes: LeadNote[];
    activities: LeadActivityItem[];
    stages: (StageRef & { type: 'open' | 'won' | 'lost' })[];
    users: UserOption[];
    priorities: Record<string, string>;
    followUpTypes: Record<string, string>;
    can: { update: boolean; move: boolean; delete: boolean; note: boolean; assign: boolean; proposals?: boolean; create_proposal?: boolean };
};

