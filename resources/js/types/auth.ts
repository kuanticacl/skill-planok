export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    phone?: string | null;
    job_title?: string | null;
    is_active?: boolean;
    is_portal?: boolean;
    client_id?: number | null;
    must_change_password?: boolean;
    role?: { id: number; name: string; slug: string } | null;
    email_verified_at: string | null;
    /* @chisel-2fa */
    two_factor_enabled?: boolean;
    /* @end-chisel-2fa */
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    /** Claves de permiso efectivas del usuario autenticado. */
    permissions: string[];
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

/* @chisel-2fa */
export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
/* @end-chisel-2fa */
