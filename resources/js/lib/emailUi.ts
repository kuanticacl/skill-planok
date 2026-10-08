export const campaignTone: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    scheduled: 'bg-brand-blue/10 text-brand-blue',
    sending: 'bg-primary/10 text-primary',
    sent: 'bg-brand-green/10 text-brand-green',
    paused: 'bg-[#FFA165]/20 text-[#9A4B00]',
    cancelled: 'bg-muted text-muted-foreground',
    failed: 'bg-destructive/10 text-destructive',
};

export const messageTone: Record<string, string> = {
    queued: 'bg-muted text-muted-foreground',
    sending: 'bg-primary/10 text-primary',
    sent: 'bg-brand-blue/10 text-brand-blue',
    delivered: 'bg-brand-green/10 text-brand-green',
    bounced: 'bg-destructive/10 text-destructive',
    complained: 'bg-[#FFA165]/20 text-[#9A4B00]',
    failed: 'bg-destructive/10 text-destructive',
    suppressed: 'bg-muted text-muted-foreground',
};
