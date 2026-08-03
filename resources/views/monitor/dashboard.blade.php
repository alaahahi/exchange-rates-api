<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitor — {{ $project }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --bg: #0b1220;
            --panel: #111827;
            --panel-2: #1a2332;
            --border: #243044;
            --text: #e5edf7;
            --muted: #8fa3bf;
            --accent: #38bdf8;
            --ok: #34d399;
            --warn: #fbbf24;
            --danger: #f87171;
            --purple: #a78bfa;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: radial-gradient(circle at top right, #172554 0%, var(--bg) 45%);
            color: var(--text);
            line-height: 1.5;
        }
        .wrap { max-width: 1440px; margin: 0 auto; padding: 24px 20px 48px; }
        .hero {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 16px; flex-wrap: wrap; margin-bottom: 20px;
        }
        .hero h1 { margin: 0 0 6px; font-size: 1.75rem; }
        .hero p { margin: 0; color: var(--muted); }
        .meta-pill {
            display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;
            background: var(--panel); border: 1px solid var(--border);
            border-radius: 999px; padding: 8px 14px; font-size: 12px; color: var(--muted);
        }
        .toolbar {
            display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
            background: var(--panel); border: 1px solid var(--border);
            border-radius: 14px; padding: 12px 14px; margin-bottom: 18px;
        }
        .toolbar label { color: var(--muted); font-size: 13px; }
        select, button, .link-btn {
            background: var(--panel-2); color: var(--text); border: 1px solid var(--border);
            padding: 8px 12px; border-radius: 10px; font-size: 13px; cursor: pointer;
        }
        button.primary { background: #1d4ed8; border-color: #2563eb; }
        button.primary:hover { background: #2563eb; }
        .link-btn { text-decoration: none; display: inline-block; }
        .status-msg { min-height: 20px; margin-bottom: 12px; font-size: 13px; }
        .status-msg.loading { color: var(--muted); }
        .status-msg.error { color: var(--danger); }
        .cards {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px; margin-bottom: 18px;
        }
        .card {
            background: linear-gradient(180deg, var(--panel-2), var(--panel));
            border: 1px solid var(--border); border-radius: 14px; padding: 14px 16px;
            min-height: 96px; overflow: hidden;
        }
        .card .label { color: var(--muted); font-size: 12px; margin-bottom: 8px; }
        .card .value {
            font-size: clamp(1.1rem, 2.5vw, 1.65rem); font-weight: 700;
            word-break: break-word; line-height: 1.2;
        }
        .card.ok .value { color: var(--ok); }
        .card.warn .value { color: var(--warn); }
        .card.danger .value { color: var(--danger); }
        .card.info .value { color: var(--accent); }
        .charts { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px; }
        .panel {
            background: var(--panel); border: 1px solid var(--border);
            border-radius: 14px; padding: 16px; margin-bottom: 14px;
        }
        .panel-head {
            display: flex; justify-content: space-between; align-items: center;
            gap: 10px; margin-bottom: 12px;
        }
        .panel-head h2 { margin: 0; font-size: 1rem; }
        .count-badge {
            background: var(--panel-2); border: 1px solid var(--border);
            border-radius: 999px; padding: 2px 10px; font-size: 12px; color: var(--muted);
        }
        .table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; font-size: 12px; min-width: 720px; }
        th, td { padding: 10px 12px; text-align: right; vertical-align: top; border-bottom: 1px solid var(--border); }
        th { background: var(--panel-2); color: var(--muted); font-weight: 600; white-space: nowrap; }
        tr:hover td { background: rgba(56, 189, 248, 0.04); }
        .mono { font-family: Consolas, monospace; font-size: 11px; direction: ltr; text-align: left; }
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 11px; font-weight: 700; border: 1px solid transparent;
        }
        .badge-error { color: #fecaca; background: rgba(248,113,113,.15); border-color: rgba(248,113,113,.35); }
        .badge-warn { color: #fde68a; background: rgba(251,191,36,.12); border-color: rgba(251,191,36,.3); }
        .badge-info { color: #bae6fd; background: rgba(56,189,248,.12); border-color: rgba(56,189,248,.3); }
        .badge-ok { color: #bbf7d0; background: rgba(52,211,153,.12); border-color: rgba(52,211,153,.3); }
        .empty { padding: 24px; text-align: center; color: var(--muted); }
        .url-cell { max-width: 280px; }
        .url-cell a { color: var(--accent); text-decoration: none; word-break: break-all; }
        .msg-cell { max-width: 420px; word-break: break-word; }
        @media (max-width: 900px) { .charts { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="hero">
        <div>
            <h1>مراقبة النظام — <span id="project-name">{{ $project }}</span></h1>
            <p>لوحة تشخيصية — كل البيانات من API (جاهزة للربط المركزي)</p>
        </div>
        <div class="meta-pill" id="meta">جاري التحميل...</div>
    </div>

    <div class="toolbar">
        <label for="date">التاريخ</label>
        <select id="date"></select>
        <button type="button" class="primary" id="refresh">تحديث</button>
        <a class="link-btn" href="{{ $apiBase }}/overview" target="_blank">Overview API</a>
        <a class="link-btn" href="{{ $apiBase }}/laravel-logs" target="_blank">Laravel Logs API</a>
    </div>
    <div class="status-msg loading" id="status-msg">جاري تحميل البيانات...</div>

    <div class="cards" id="summary-cards"></div>

    <div class="charts">
        <div class="panel">
            <div class="panel-head"><h2>الطلبات في الدقيقة</h2></div>
            <canvas id="rpmChart" height="110"></canvas>
        </div>
        <div class="panel">
            <div class="panel-head"><h2>استهلاك الذاكرة (MB)</h2></div>
            <canvas id="memoryChart" height="110"></canvas>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>تنبيهات</h2><span class="count-badge" id="alerts-count">0</span></div>
        <div id="alerts-table"></div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>طلبات بطيئة</h2><span class="count-badge" id="slow-req-count">0</span></div>
        <div id="slow-requests-table"></div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>استعلامات بطيئة</h2><span class="count-badge" id="slow-q-count">0</span></div>
        <div id="slow-queries-table"></div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>استثناءات قاعدة البيانات</h2><span class="count-badge" id="exc-count">0</span></div>
        <div id="exceptions-table"></div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Laravel Application Log</h2><span class="count-badge" id="laravel-count">0</span></div>
        <div class="toolbar" style="margin-bottom:12px;padding:10px;">
            <label for="laravel-file">الملف</label>
            <select id="laravel-file"></select>
            <label for="laravel-level">المستوى</label>
            <select id="laravel-level">
                <option value="">الكل</option>
                <option value="ERROR">ERROR</option>
                <option value="WARNING">WARNING</option>
                <option value="INFO">INFO</option>
                <option value="DEBUG">DEBUG</option>
            </select>
            <button type="button" id="laravel-refresh">تحديث اللوغ</button>
        </div>
        <div id="laravel-logs-table"></div>
    </div>
</div>

<script>
const API_BASE = @json($apiBase);
let rpmChart = null;
let memoryChart = null;

function esc(v) {
    return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function fmtNum(n) {
    const x = Number(n);
    if (!Number.isFinite(x)) return '-';
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(x);
}

function fmtMs(ms) {
    const x = Number(ms);
    if (!Number.isFinite(x) || x <= 0) return '0 ms';
    if (x >= 60000) return (x / 60000).toFixed(1) + ' min';
    if (x >= 1000) return (x / 1000).toFixed(2) + ' s';
    return fmtNum(x) + ' ms';
}

function fmtTime(ts) {
    if (!ts) return '-';
    try {
        const d = new Date(ts);
        return d.toLocaleString('ar-IQ', { hour: '2-digit', minute: '2-digit', second: '2-digit', year: 'numeric', month: '2-digit', day: '2-digit' });
    } catch { return ts; }
}

function shortUrl(url) {
    if (!url) return '-';
    try {
        const u = new URL(url);
        return u.pathname + (u.search ? u.search.slice(0, 40) : '');
    } catch {
        return String(url).slice(0, 60);
    }
}

function metricLabel(metric) {
    const map = {
        response_time_ms: 'زمن الاستجابة',
        query_time_ms: 'زمن الاستعلامات',
        memory_mb: 'الذاكرة',
        threads_connected: 'اتصالات MySQL',
    };
    return map[metric] || metric || '-';
}

function levelBadge(level) {
    const l = String(level || '').toUpperCase();
    let cls = 'badge-info';
    if (['ERROR','CRITICAL','ALERT','EMERGENCY'].includes(l)) cls = 'badge-error';
    else if (l === 'WARNING') cls = 'badge-warn';
    else if (l === 'DEBUG') cls = 'badge-ok';
    return `<span class="badge ${cls}">${esc(l || '-')}</span>`;
}

function tableHtml(columns, rows, emptyText) {
    if (!rows?.length) return `<div class="empty">${esc(emptyText)}</div>`;
    const head = columns.map(c => `<th>${esc(c.label)}</th>`).join('');
    const body = rows.map(row => {
        return '<tr>' + columns.map(c => {
            const raw = typeof c.render === 'function' ? c.render(row) : row[c.key];
            const html = c.html ? raw : esc(raw);
            const cls = c.class ? ` class="${c.class}"` : '';
            return `<td${cls}>${html ?? '-'}</td>`;
        }).join('') + '</tr>';
    }).join('');
    return `<div class="table-wrap"><table><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table></div>`;
}

function renderSummary(summary, status) {
    const cards = [
        { label: 'اتصالات MySQL', value: status?.threads_connected ?? summary?.threads_connected ?? 0, tone: 'info' },
        { label: 'أقصى اتصالات اليوم', value: summary?.max_connections_today ?? 0, tone: 'info' },
        { label: 'عدد الطلبات', value: summary?.total_requests ?? 0, tone: 'ok' },
        { label: 'متوسط الاستجابة', value: fmtMs(summary?.avg_response_ms ?? 0), tone: 'warn' },
        { label: 'طلبات بطيئة', value: summary?.slow_requests_count ?? 0, tone: (summary?.slow_requests_count > 0 ? 'warn' : 'ok') },
        { label: 'طلبات فاشلة', value: summary?.failed_requests_count ?? 0, tone: (summary?.failed_requests_count > 0 ? 'danger' : 'ok') },
        { label: 'استثناءات SQL', value: summary?.exceptions_count ?? 0, tone: (summary?.exceptions_count > 0 ? 'danger' : 'ok') },
        { label: 'Queue Jobs', value: summary?.queue_jobs_count ?? 0, tone: 'info' },
    ];
    document.getElementById('summary-cards').innerHTML = cards.map(c =>
        `<div class="card ${c.tone}"><div class="label">${esc(c.label)}</div><div class="value">${typeof c.value === 'number' ? fmtNum(c.value) : esc(c.value)}</div></div>`
    ).join('');
}

function renderCharts(metrics) {
    const rpm = metrics?.requests_per_minute ?? { labels: [], values: [] };
    const mem = metrics?.memory_trend ?? { labels: [], values: [] };
    const opts = {
        responsive: true,
        plugins: { legend: { labels: { color: '#cbd5e1', boxWidth: 12 } } },
        scales: {
            x: { ticks: { color: '#94a3b8', maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, grid: { color: 'rgba(148,163,184,.08)' } },
            y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,.08)' } }
        }
    };
    if (rpmChart) rpmChart.destroy();
    if (memoryChart) memoryChart.destroy();
    rpmChart = new Chart(document.getElementById('rpmChart'), {
        type: 'bar',
        data: { labels: rpm.labels.map(l => l.slice(11, 16)), datasets: [{ label: 'طلب/دقيقة', data: rpm.values, backgroundColor: 'rgba(56,189,248,.55)', borderRadius: 4 }] },
        options: opts
    });
    memoryChart = new Chart(document.getElementById('memoryChart'), {
        type: 'line',
        data: { labels: mem.labels, datasets: [{ label: 'Peak MB', data: mem.values, borderColor: '#34d399', backgroundColor: 'rgba(52,211,153,.12)', fill: true, tension: .3 }] },
        options: opts
    });
}

function renderTables(metrics, alerts) {
    document.getElementById('alerts-count').textContent = alerts?.length ?? 0;
    document.getElementById('slow-req-count').textContent = metrics?.slow_requests?.length ?? 0;
    document.getElementById('slow-q-count').textContent = metrics?.slow_queries?.length ?? 0;
    document.getElementById('exc-count').textContent = metrics?.exceptions?.length ?? 0;

    document.getElementById('alerts-table').innerHTML = tableHtml([
        { label: 'الوقت', render: r => fmtTime(r.timestamp) },
        { label: 'المقياس', render: r => metricLabel(r.metric) },
        { label: 'القيمة', render: r => r.metric?.includes('time') ? fmtMs(r.value) : fmtNum(r.value) },
        { label: 'الحد', render: r => r.metric?.includes('time') ? fmtMs(r.threshold) : fmtNum(r.threshold) },
        { label: 'المصدر', class: 'url-cell', html: true, render: r => {
            const src = r.url || r.route || '-';
            return r.url ? `<a href="${esc(r.url)}" target="_blank" title="${esc(r.url)}">${esc(shortUrl(r.url))}</a>` : esc(src);
        }},
    ], alerts, 'لا توجد تنبيهات');

    document.getElementById('slow-requests-table').innerHTML = tableHtml([
        { label: 'الوقت', render: r => fmtTime(r.timestamp) },
        { label: 'المسار', class: 'url-cell', render: r => shortUrl(r.url) || r.route || '-' },
        { label: 'المدة', render: r => fmtMs(r.execution_time_ms) },
        { label: 'الحالة', html: true, render: r => {
            const s = Number(r.status || 0);
            const cls = s >= 500 ? 'badge-error' : (s >= 400 ? 'badge-warn' : 'badge-ok');
            return `<span class="badge ${cls}">${esc(r.status ?? '-')}</span>`;
        }},
        { label: 'Threads', render: r => r.database?.threads_connected ?? '-' },
    ], metrics?.slow_requests, 'لا يوجد');

    document.getElementById('slow-queries-table').innerHTML = tableHtml([
        { label: 'المسار', class: 'url-cell', render: r => shortUrl(r.url) || r.route || '-' },
        { label: 'المدة', render: r => fmtMs(r.time_ms) },
        { label: 'SQL', class: 'mono msg-cell', render: r => (r.sql || '').slice(0, 200) },
    ], metrics?.slow_queries, 'لا يوجد');

    document.getElementById('exceptions-table').innerHTML = tableHtml([
        { label: 'الوقت', render: r => fmtTime(r.timestamp) },
        { label: 'النوع', class: 'mono', render: r => (r.exception_class || '').split('\\').pop() },
        { label: 'الرسالة', class: 'msg-cell', render: r => (r.message || '').slice(0, 250) },
    ], metrics?.exceptions, 'لا يوجد');
}

function fillDateSelect(dates, selected) {
    const list = dates?.length ? dates : [selected];
    document.getElementById('date').innerHTML = list.map(d =>
        `<option value="${esc(d)}" ${d === selected ? 'selected' : ''}>${esc(d)}</option>`
    ).join('');
}

function fillLaravelFileSelect(files, selected) {
    const select = document.getElementById('laravel-file');
    if (!files?.length) {
        select.innerHTML = '<option value="laravel.log">laravel.log</option>';
        return;
    }
    select.innerHTML = files.map(f => {
        const name = f.file || f;
        return `<option value="${esc(name)}" ${name === selected ? 'selected' : ''}>${esc(name)}</option>`;
    }).join('');
}

function renderLaravelLogs(data) {
    const rows = data?.recent || data?.entries || [];
    document.getElementById('laravel-count').textContent = rows.length;
    fillLaravelFileSelect(data?.available_files, data?.file);
    document.getElementById('laravel-logs-table').innerHTML = tableHtml([
        { label: 'الوقت', render: r => esc(r.timestamp) },
        { label: 'المستوى', html: true, render: r => levelBadge(r.level) },
        { label: 'القناة', render: r => r.channel },
        { label: 'الرسالة', class: 'msg-cell', render: r => (r.message || '').slice(0, 350) },
    ], rows, 'لا يوجد Laravel log — تأكد من رفع آخر نسخة من الموديل على السيرفر');
}

async function loadLaravelLogs() {
    const file = document.getElementById('laravel-file').value;
    const level = document.getElementById('laravel-level').value;
    const params = new URLSearchParams({ file, limit: '100' });
    if (level) params.set('level', level);
    try {
        const [logsRes, filesRes] = await Promise.all([
            fetch(`${API_BASE}/laravel-logs?${params}`),
            fetch(`${API_BASE}/laravel-log-files`),
        ]);
        if (!logsRes.ok) throw new Error(logsRes.status === 404 ? 'المسار غير موجود — ارفع آخر تحديث للموديل' : `HTTP ${logsRes.status}`);
        const data = await logsRes.json();
        const files = filesRes.ok ? (await filesRes.json()).files : [];
        renderLaravelLogs({ file: data.file, available_files: files, recent: data.entries });
    } catch (e) {
        document.getElementById('laravel-logs-table').innerHTML = `<div class="empty" style="color:var(--danger)">${esc(e.message)}</div>`;
    }
}

async function loadOverview(date) {
    const statusEl = document.getElementById('status-msg');
    statusEl.textContent = 'جاري تحميل البيانات...';
    statusEl.className = 'status-msg loading';
    try {
        const res = await fetch(`${API_BASE}/overview?date=${encodeURIComponent(date || '')}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        document.getElementById('project-name').textContent = data.project || @json($project);
        document.getElementById('meta').innerHTML = [
            `<span>${esc(data.hostname || '-')}</span>`,
            `<span>${esc(data.environment || '-')}</span>`,
            `<span>${fmtTime(data.server_time)}</span>`,
        ].join(' · ');
        fillDateSelect(data.available_dates, data.date);
        renderSummary(data.metrics?.summary, data.status);
        renderCharts(data.metrics);
        renderTables(data.metrics, data.alerts);
        if (data.laravel_logs) renderLaravelLogs(data.laravel_logs);
        else loadLaravelLogs();
        statusEl.textContent = '';
    } catch (e) {
        statusEl.textContent = 'فشل تحميل البيانات: ' + e.message;
        statusEl.className = 'status-msg error';
    }
}

document.getElementById('date').addEventListener('change', e => loadOverview(e.target.value));
document.getElementById('refresh').addEventListener('click', () => loadOverview(document.getElementById('date').value));
document.getElementById('laravel-refresh').addEventListener('click', loadLaravelLogs);
document.getElementById('laravel-level').addEventListener('change', loadLaravelLogs);

loadOverview(new Date().toISOString().slice(0, 10));
</script>
</body>
</html>
