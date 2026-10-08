/**
 * Utilidades del texto enriquecido (HTML). Los textos antiguos se guardaban como «markdown mínimo»
 * (**negrita**, listas con «- »); se convierten a HTML al abrirlos en el editor.
 */

export const isHtml = (s: string): boolean => /^\s*<(p|ul|ol|h[1-6]|div|blockquote|br)[\s>/]/i.test(s);

const esc = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

const inline = (line: string): string => {
    let t = esc(line);
    t = t.replace(/\[([^\]]+)\]\((https:\/\/[^\s)]+)\)/g, '<a href="$2">$1</a>');
    t = t.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>').replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>');

    return t;
};

/** Markdown mínimo → HTML (párrafos, listas con «- », negrita, cursiva y enlaces https). */
export function legacyToHtml(text: string): string {
    const src = (text ?? '').replace(/\r/g, '').trim();
    if (!src) return '';
    if (isHtml(src)) return src;

    return src
        .split(/\n{2,}/)
        .map((block) => {
            const lines = block.split('\n');
            if (lines.every((l) => /^\s*[-•*]\s+/.test(l))) return `<ul>${lines.map((l) => `<li>${inline(l.replace(/^\s*[-•*]\s+/, ''))}</li>`).join('')}</ul>`;

            return `<p>${lines.map(inline).join('<br>')}</p>`;
        })
        .join('');
}

const ALLOWED = new Set(['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'S', 'UL', 'OL', 'LI', 'H3', 'H4', 'A', 'BLOCKQUOTE']);

/** Deja solo etiquetas y atributos seguros (para vista previa y correos). */
export function sanitizeHtml(html: string): string {
    const doc = new DOMParser().parseFromString(`<body>${html ?? ''}</body>`, 'text/html');
    const walk = (node: Element) => {
        [...node.children].forEach((el) => {
            walk(el);
            if (['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT'].includes(el.tagName)) return el.remove();
            if (!ALLOWED.has(el.tagName)) return el.replaceWith(...el.childNodes);
            [...el.attributes].forEach((a) => {
                const ok = el.tagName === 'A' && a.name === 'href' && /^(https:|mailto:|\{\{)/i.test(a.value.trim());
                if (!ok) el.removeAttribute(a.name);
            });
        });
    };
    walk(doc.body);

    return doc.body.innerHTML;
}

/** Texto plano (para listas y vistas resumidas). */
export const stripHtml = (html: string): string => (html ?? '').replace(/<\/(p|li|h\d)>/gi, ' ').replace(/<br\s*\/?>/gi, ' ').replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim();
