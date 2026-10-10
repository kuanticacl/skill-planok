/** Markdown mínimo y seguro para las respuestas del Agent: negrita, cursiva, código, listas y enlaces del propio CRM. */
const esc = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const inline = (s: string) =>
    esc(s)
        .replace(/`([^`]+)`/g, '<code class="rounded bg-muted px-1 py-0.5 text-[0.85em]">$1</code>')
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/(^|[\s(])\*([^*\n]+)\*/g, '$1<em>$2</em>')
        .replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+|\/[^)\s]*)\)/g, (_m, t: string, u: string) => {
            const internal = u.startsWith('/') || u.startsWith(window.location.origin);
            return `<a href="${u}" ${internal ? '' : 'target="_blank" rel="noopener noreferrer"'} class="font-medium text-primary underline underline-offset-2">${t}</a>`;
        })
        .replace(/(^|[\s(])(https?:\/\/[^\s<)]+)/g, (_m, p: string, u: string) => {
            const internal = u.startsWith(window.location.origin);
            return `${p}<a href="${u}" ${internal ? '' : 'target="_blank" rel="noopener noreferrer"'} class="font-medium text-primary underline underline-offset-2 break-all">${u}</a>`;
        });

export function renderMarkdown(src: string): string {
    const out: string[] = [];
    let list: 'ul' | 'ol' | null = null;
    const close = () => {
        if (list) out.push(`</${list}>`);
        list = null;
    };
    for (const raw of src.split('\n')) {
        const line = raw.trimEnd();
        const ul = /^\s*[-*•]\s+(.*)$/.exec(line);
        const ol = /^\s*\d+[.)]\s+(.*)$/.exec(line);
        if (ul || ol) {
            const kind = ul ? 'ul' : 'ol';
            if (list !== kind) {
                close();
                out.push(kind === 'ul' ? '<ul class="my-1 list-disc space-y-0.5 pl-5">' : '<ol class="my-1 list-decimal space-y-0.5 pl-5">');
                list = kind;
            }
            out.push(`<li>${inline((ul ?? ol)![1])}</li>`);
            continue;
        }
        close();
        const h = /^#{1,4}\s+(.*)$/.exec(line);
        if (h) out.push(`<p class="mt-2 font-semibold">${inline(h[1])}</p>`);
        else if (line.trim() === '') out.push('<div class="h-2"></div>');
        else out.push(`<p>${inline(line)}</p>`);
    }
    close();
    return out.join('');
}
