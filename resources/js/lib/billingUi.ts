// Utilidades de servicios contratados, cobros y portal de clientes.
const nf0 = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 });
const nf2 = new Intl.NumberFormat('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export type Currency = 'CLP' | 'UF';

/** $119.000 · UF 12,50 */
export const money = (v: number | null | undefined, currency: string = 'CLP'): string =>
    v === null || v === undefined ? '—' : currency === 'UF' ? `UF ${nf2.format(v)}` : `$${nf0.format(v)}`;

/** Fechas «Y-m-d» sin desfase de zona horaria. */
export const fmtDate = (d: string | null | undefined): string =>
    d ? new Intl.DateTimeFormat('es-CL', { dateStyle: 'medium' }).format(new Date(`${d.slice(0, 10)}T12:00:00`)) : '—';

export const daysUntil = (d: string | null | undefined): number | null =>
    d ? Math.round((new Date(`${d.slice(0, 10)}T12:00:00`).getTime() - new Date(new Date().toDateString()).getTime() - 12 * 3600 * 1000) / 86400000) : null;

export const cycleLabels: Record<string, string> = { one_time: 'Pago único', monthly: 'Mensual', quarterly: 'Trimestral', yearly: 'Anual' };
export const cyclePer: Record<string, string> = { one_time: '', monthly: '/ mes', quarterly: '/ trimestre', yearly: '/ año' };

type Meta = { label: string; cls: string };

export const serviceStatus: Record<string, Meta> = {
    active: { label: 'Activo', cls: 'bg-brand-green/10 text-brand-green' },
    pending: { label: 'Por iniciar', cls: 'bg-sky-500/10 text-sky-600 dark:text-sky-400' },
    paused: { label: 'Pausado', cls: 'bg-amber-500/10 text-amber-600 dark:text-amber-400' },
    ended: { label: 'Finalizado', cls: 'bg-muted text-muted-foreground' },
    cancelled: { label: 'Cancelado', cls: 'bg-muted text-muted-foreground' },
};

export const invoiceStatus: Record<string, Meta> = {
    scheduled: { label: 'Por emitir', cls: 'bg-muted text-muted-foreground' },
    issued: { label: 'Por pagar', cls: 'bg-sky-500/10 text-sky-600 dark:text-sky-400' },
    overdue: { label: 'Vencida', cls: 'bg-red-500/10 text-red-600 dark:text-red-400' },
    paid: { label: 'Pagada', cls: 'bg-brand-green/10 text-brand-green' },
    cancelled: { label: 'Anulada', cls: 'bg-muted text-muted-foreground line-through' },
};

export const offsetLabel = (o: number): string => (o === 0 ? 'El día del vencimiento' : o < 0 ? `${Math.abs(o)} día${o === -1 ? '' : 's'} antes` : `${o} día${o === 1 ? '' : 's'} después`);
