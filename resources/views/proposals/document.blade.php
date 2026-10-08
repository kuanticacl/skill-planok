@php
    use App\Support\ProposalText;
    use App\Models\Proposal;

    $r = $p->recipient ?? [];
    $money = fn ($n) => '$'.number_format((int) $n, 0, ',', '.');
    $months = max(1, (int) ($p->contract_months ?: 1));
    $items = $p->items;
    $once = $items->where('billing', 'one_time');
    $monthly = $items->where('billing', 'monthly');
    $status = $p->effectiveStatus();
    $sections = collect($p->sections ?? [])->map(fn ($s) => ['title' => $s['title'], 'body' => ProposalText::html(ProposalText::fill($s['body'], $r))])->values();
    $servicesAt = $sections->search(fn ($s) => preg_match('/alcance|servicio/i', $s['title']));
    $subOnce = $once->sum(fn ($i) => $i->lineTotal());
    $subMonthly = $monthly->sum(fn ($i) => $i->lineTotal());
    $discount = ($subOnce + $subMonthly * $months) - ($p->total_one_time + $p->total_monthly * $months);
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->locale('es')->translatedFormat('d \d\e F \d\e Y') : null;
    $company = $r['company'] ?? $r['legal_name'] ?? 'Cliente';
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $p->title }} · Quiebre</title>
    @if ($public ?? false)<meta name="robots" content="noindex,nofollow">@endif
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Asap:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --o:#FF5300; --o2:#FFA165; --ink:#393939; --mut:#707070; --line:#E8E8E8; --bg:#F4F4F4; --soft:#FFF3EC; }
        * { box-sizing: border-box; }
        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin:0; background:var(--bg); color:var(--ink); font-family:'Asap',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.6; }
        .wrap { max-width:880px; margin:0 auto; padding:24px 16px 64px; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:8px 4px 20px; }
        .top svg { height:26px; width:auto; color:var(--ink); }
        .pill { display:inline-flex; align-items:center; gap:8px; border-radius:999px; font-weight:600; font-family:inherit; font-size:14px; padding:10px 20px; text-decoration:none; cursor:pointer; border:1px solid var(--line); background:#fff; color:var(--ink); }
        .pill.primary { background:var(--o); border-color:var(--o); color:#fff; }
        .pill:hover { filter:brightness(.97); }
        .card { background:#fff; border-radius:24px; padding:32px; margin-bottom:16px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
        .cover { position:relative; overflow:hidden; background:var(--o); color:#fff; border-radius:28px; padding:48px 40px 40px; margin-bottom:16px; }
        .cover::before { content:''; position:absolute; right:-70px; top:-60px; width:300px; height:300px; border-radius:90px; background:rgba(255,255,255,.12); }
        .cover::after { content:''; position:absolute; right:30px; top:30px; width:150px; height:150px; border-radius:48px; background:rgba(255,255,255,.14); }
        .cover .tag { display:inline-block; background:rgba(255,255,255,.2); border-radius:999px; padding:4px 14px; font-size:13px; font-weight:600; letter-spacing:.02em; }
        .cover h1 { position:relative; margin:18px 0 8px; font-size:36px; line-height:1.15; font-weight:700; max-width:620px; }
        .cover p { position:relative; margin:0; font-size:17px; opacity:.95; }
        .cover .meta { position:relative; display:flex; flex-wrap:wrap; gap:28px; margin-top:32px; }
        .cover .meta div span { display:block; font-size:12px; opacity:.8; text-transform:uppercase; letter-spacing:.06em; }
        .cover .meta div strong { font-size:15px; }
        h2 { margin:0 0 12px; font-size:21px; line-height:1.25; display:flex; align-items:center; gap:12px; }
        h2 .n { flex:none; width:32px; height:32px; border-radius:11px; background:var(--soft); color:var(--o); font-size:14px; font-weight:700; display:grid; place-items:center; }
        .body p { margin:0 0 10px; } .body ul { margin:0 0 10px; padding-left:20px; } .body li { margin:3px 0; } .body li::marker { color:var(--o); }
        .body a { color:var(--o); }
        dl.info { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px 24px; margin:0; }
        dl.info dt { font-size:12px; color:var(--mut); text-transform:uppercase; letter-spacing:.05em; } dl.info dd { margin:0; font-weight:600; }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; font-size:12px; color:var(--mut); text-transform:uppercase; letter-spacing:.05em; padding:0 10px 8px; border-bottom:2px solid var(--o); }
        td { padding:12px 10px; border-bottom:1px solid var(--line); vertical-align:top; }
        td.r, th.r { text-align:right; white-space:nowrap; }
        .iname { font-weight:700; } .idesc { color:var(--mut); font-size:13.5px; margin-top:2px; }
        .deliv { margin:6px 0 0; padding-left:18px; font-size:13.5px; color:var(--mut); } .deliv li::marker { color:var(--o); }
        .grp { display:flex; align-items:center; gap:10px; margin:22px 0 8px; font-weight:700; }
        .grp .dot { width:10px; height:10px; border-radius:50%; background:var(--o); } .grp small { font-weight:500; color:var(--mut); }
        .totals { margin:24px 0 0 auto; max-width:380px; }
        .totals div { display:flex; justify-content:space-between; padding:5px 0; } .totals .sep { border-top:1px solid var(--line); margin-top:6px; padding-top:10px; }
        .totals .grand { background:var(--o); color:#fff; border-radius:16px; padding:14px 18px; margin-top:10px; font-size:18px; font-weight:700; }
        .kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; margin-bottom:8px; }
        .kpi { background:var(--soft); border-radius:18px; padding:16px 18px; } .kpi span { font-size:12px; color:var(--mut); text-transform:uppercase; letter-spacing:.05em; } .kpi strong { display:block; font-size:24px; color:var(--o); line-height:1.2; }
        .accept { border:2px solid var(--o); } .accept form { display:grid; gap:12px; margin-top:12px; }
        .accept input, .accept textarea { font:inherit; width:100%; border:1px solid var(--line); border-radius:14px; padding:11px 14px; background:#fff; color:var(--ink); }
        .accept input:focus, .accept textarea:focus { outline:2px solid var(--o2); border-color:var(--o); }
        .actions { display:flex; flex-wrap:wrap; gap:10px; }
        .badge { display:inline-block; border-radius:999px; padding:4px 14px; font-weight:600; font-size:13px; color:#fff; }
        .foot { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:20px 8px; color:var(--mut); font-size:13px; }
        .foot svg { height:20px; width:auto; color:var(--ink); } .foot a { color:var(--o); text-decoration:none; font-weight:600; }
        .flash { background:#E7F8F3; color:#0A7A66; border-radius:16px; padding:12px 18px; margin-bottom:16px; font-weight:600; }
        .err { background:#FDECEC; color:#B42318; border-radius:16px; padding:12px 18px; margin-bottom:16px; }
        .vp { position:fixed; right:12px; bottom:12px; background:#393939; color:#fff; border-radius:999px; padding:6px 14px; font-size:12px; opacity:.85; }
        @media (max-width:640px) { .card { padding:22px 18px; border-radius:20px; } .cover { padding:32px 22px; } .cover h1 { font-size:27px; } th.hide, td.hide { display:none; } }
        @media print {
            body { background:#fff; font-size:13.5px; } .wrap { max-width:none; padding:0; } .noprint, .vp { display:none !important; }
            .card, .cover { box-shadow:none; break-inside:avoid; margin-bottom:12px; } .card { border:1px solid var(--line); } tr { break-inside:avoid; }
            @page { size:A4; margin:14mm; }
        }
    </style>
</head>
<body>
<div class="wrap">
    @if (session('proposal_ok'))<div class="flash noprint">{{ session('proposal_ok') }}</div>@endif
    @if (isset($errors) && $errors->any())<div class="err noprint">{{ $errors->first() }}</div>@endif

    <div class="top noprint">
        @include('proposals._logo')
        <div class="actions">
            @if ($public ?? false)<button type="button" class="pill" onclick="window.print()">Descargar PDF</button>@endif
        </div>
    </div>

    <section class="cover">
        <span class="tag">Propuesta comercial · N.º {{ $p->number }}</span>
        <h1>{{ $p->title }}</h1>
        <p>Preparada para <strong>{{ $company }}</strong></p>
        <div class="meta">
            <div><span>Fecha</span><strong>{{ $date($p->issued_at ?? now()) }}</strong></div>
            @if ($p->valid_until)<div><span>Válida hasta</span><strong>{{ $date($p->valid_until) }}</strong></div>@endif
            @if ($p->owner)<div><span>Responsable</span><strong>{{ $p->owner->name }}</strong></div>@endif
        </div>
    </section>

    @if (array_filter($r))
    <section class="card">
        <h2>Datos del cliente</h2>
        <dl class="info">
            @foreach ([['Razón social','legal_name'],['RUT','rut'],['Nombre de fantasía','company'],['Giro','activity'],['Dirección','address'],['Contacto','contact_name'],['Cargo','contact_role'],['Correo','email'],['Teléfono','phone']] as [$label,$key])
                @if (! empty($r[$key]))<div><dt>{{ $label }}</dt><dd>{{ $r[$key] }}</dd></div>@endif
            @endforeach
        </dl>
    </section>
    @endif

    @php $n = 0; @endphp

    @foreach ($sections as $i => $s)
        @php $n++; @endphp
        <section class="card">
            <h2><span class="n">{{ $n }}</span>{{ $s['title'] }}</h2>
            <div class="body">{!! $s['body'] !!}</div>
        </section>
        @if ($servicesAt === $i) @include('proposals._services') @php $n++; @endphp @endif
    @endforeach
    @if ($servicesAt === false || $servicesAt === null) @include('proposals._services') @endif

    @if (true)
        @if (in_array($status, ['accepted', 'rejected']))
            <section class="card" style="border:2px solid {{ Proposal::STATUS_COLORS[$status] }}">
                <span class="badge" style="background:{{ Proposal::STATUS_COLORS[$status] }}">{{ Proposal::STATUSES[$status] }}</span>
                <p style="margin:10px 0 0"><strong>{{ $p->responded_by }}</strong> · {{ $p->responded_at?->locale('es')->translatedFormat('d \d\e F \d\e Y, H:i') }}</p>
                @if ($p->response_note)<p style="margin:6px 0 0;color:var(--mut)">{{ $p->response_note }}</p>@endif
            </section>
        @elseif ($status === 'expired')
            <section class="card"><span class="badge" style="background:{{ Proposal::STATUS_COLORS['expired'] }}">Vencida</span><p style="margin:10px 0 0">Esta propuesta venció el {{ $date($p->valid_until) }}. Escríbenos para actualizarla.</p></section>
        @elseif (($public ?? false) && $status !== 'draft')
            <section class="card accept noprint">
                <h2>¿Aceptas esta propuesta?</h2>
                <p style="margin:0;color:var(--mut)">Al aceptar quedará registrada tu conformidad con los servicios y valores indicados, y nuestro equipo se pondrá en contacto para comenzar.</p>
                <form method="post" action="{{ route('proposals.public.respond', $p->public_token) }}">
                    @csrf
                    <input name="name" required maxlength="160" placeholder="Tu nombre completo" value="{{ old('name', $r['contact_name'] ?? '') }}">
                    <textarea name="note" rows="2" maxlength="1000" placeholder="Comentarios (opcional)">{{ old('note') }}</textarea>
                    <div class="actions">
                        <button class="pill primary" name="action" value="accept" type="submit">Aceptar propuesta</button>
                        <button class="pill" name="action" value="reject" type="submit" onclick="return confirm('¿Seguro que quieres rechazar la propuesta?')">Rechazar</button>
                    </div>
                </form>
            </section>
        @endif
    @endif

    <div class="foot">
        <span>@include('proposals._logo')</span>
        <span>Inteligencia inmobiliaria · <a href="https://www.quiebre.cl">quiebre.cl</a></span>
    </div>
</div>
@if ($preview ?? false)<div class="vp noprint">Vista previa</div>@endif
</body>
</html>
