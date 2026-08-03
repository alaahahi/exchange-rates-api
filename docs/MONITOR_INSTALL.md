# Laravel Monitor Module — نسخ وربط مركزي

وحدة مراقبة **بدون جداول DB** — تكتب JSONL في `storage/logs/monitor/` وتعرض Laravel logs عبر API عام.

متوافقة مع **Laravel 9+** و **PHP 8.0+**.

---

## ماذا توفر؟

| الميزة | الوصف |
|--------|--------|
| Request telemetry | مدة، ذاكرة، استعلامات، DB snapshot |
| Exceptions | أخطاء SQL والاتصال |
| Queue / Scheduler | مهام الخلفية والـ cron |
| Alerts | تنبيهات عند تجاوز الحدود |
| Monitor JSONL API | بيانات مجمّعة لكل نظام |
| **Laravel Log API** | قراءة `storage/logs/laravel*.log` للعرض المركزي |
| Dashboard | واجهة HTML تقرأ **فقط من API** |

---

## API — بدون مصادقة (افتراضياً)

كل استجابة تحتوي: `project`, `hostname`, `environment`, `server_time`.

### نقاط النهاية الرئيسية

| Endpoint | الاستخدام |
|----------|-----------|
| `GET /monitor/api/overview?date=YYYY-MM-DD` | **الأهم للنظام المركزي** — status + metrics + alerts + laravel_logs |
| `GET /monitor/api/status` | MySQL + الذاكرة الحية |
| `GET /monitor/api/metrics?date=` | مقاييس من JSONL |
| `GET /monitor/api/alerts?limit=100` | التنبيهات |
| `GET /monitor/api/logs?date=&type=` | سجلات JSONL الخام |
| `GET /monitor/api/dates` | تواريخ ملفات المراقبة |
| `GET /monitor/api/laravel-logs?file=&level=&search=&limit=` | **Laravel application log** |
| `GET /monitor/api/laravel-log-files` | قائمة ملفات `laravel*.log` |
| `GET /monitor/dashboard` | واجهة محلية (اختياري) |

### مثال Laravel Log API

```http
GET /monitor/api/laravel-logs?file=laravel.log&level=ERROR&limit=50
```

```json
{
  "project": "Shipping ERP",
  "hostname": "server-1",
  "environment": "production",
  "server_time": "2026-07-16T10:00:00+00:00",
  "file": "laravel.log",
  "total": 12,
  "limit": 50,
  "entries": [
    {
      "timestamp": "2026-07-16 09:55:01",
      "channel": "local",
      "level": "ERROR",
      "message": "SQLSTATE[HY000] ...",
      "context": null,
      "raw": "[2026-07-16 09:55:01] local.ERROR: ..."
    }
  ]
}
```

### مثال النظام المركزي (JavaScript)

```javascript
const SYSTEMS = [
  { name: 'Shipping', url: 'https://shipping.example.com/monitor/api/overview' },
  { name: 'ERP-2',    url: 'https://erp2.example.com/monitor/api/overview' },
];

async function pollAll() {
  const results = await Promise.allSettled(
    SYSTEMS.map(async (s) => ({
      ...s,
      data: await fetch(s.url).then((r) => r.json()),
    }))
  );
  return results;
}

async function fetchLaravelErrors(baseUrl) {
  const res = await fetch(`${baseUrl}/laravel-logs?level=ERROR&limit=30`);
  return res.json();
}
```

---

## نسخ سريع لمشروع Laravel آخر

### الطريقة 1 — سكربت PowerShell (Windows)

من مجلد المشروع المصدر (`shipping`):

```powershell
.\scripts\copy-monitor-module.ps1 -Target "C:\xampp\htdocs\other-project"
```

### الطريقة 2 — سكربت Bash (Linux/Mac)

```bash
./scripts/copy-monitor-module.sh /var/www/other-project
```

### الطريقة 3 — نسخ يدوي

انسخ هذه المسارات كما هي:

```
app/Monitor/                          → app/Monitor/
config/monitor.php                    → config/monitor.php
resources/views/monitor/              → resources/views/monitor/
docs/MONITOR_INSTALL.md               → docs/MONITOR_INSTALL.md
scripts/copy-monitor-module.ps1       → scripts/  (اختياري)
scripts/copy-monitor-module.sh        → scripts/  (اختياري)
tests/Unit/Monitor/                   → tests/Unit/Monitor/  (اختياري)
tests/Feature/Monitor/                → tests/Feature/Monitor/ (اختياري)
```

---

## خطوات التثبيت (Checklist)

### 1) تسجيل Service Provider

**Laravel 9** — `config/app.php`:

```php
'providers' => [
    // ...
    App\Monitor\Providers\MonitorServiceProvider::class,
],
```

**Laravel 11+** — `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Monitor\Providers\MonitorServiceProvider::class,
];
```

### 2) ربط Exception Handler

في `app/Exceptions/Handler.php` داخل `register()`:

```php
$this->reportable(function (Throwable $e) {
    if (app()->bound(\App\Monitor\Services\ExceptionMonitor::class)) {
        app(\App\Monitor\Services\ExceptionMonitor::class)->log($e);
    }
});
```

### 3) إعداد `.env` — **مهم لكل مشروع**

```env
MONITOR_ENABLED=true
MONITOR_PROJECT_NAME="اسم المشروع الفريد"
MONITOR_CORS_ORIGIN=*

# Laravel logs في API
MONITOR_LARAVEL_LOG_ENABLED=true
MONITOR_LARAVEL_LOG_PATTERNS=laravel.log,laravel-*.log
MONITOR_LARAVEL_LOG_IN_OVERVIEW=true

# اختياري — حماية لاحقاً
MONITOR_API_MIDDLEWARE=
```

> `MONITOR_PROJECT_NAME` يميّز كل نظام في اللوحة المركزية.

### 4) صلاحيات المجلدات

```bash
mkdir -p storage/logs/monitor
chmod -R 775 storage/logs storage/logs/monitor
```

### 5) اختبار

```bash
php artisan test tests/Unit/Monitor tests/Feature/Monitor
curl http://localhost/monitor/api/overview
curl "http://localhost/monitor/api/laravel-logs?level=ERROR&limit=10"
```

---

## تخصيص مرن

كل شيء عبر `config/monitor.php` أو `.env` — **لا حاجة لتعديل الكود** في أغلب الحالات.

| الإعداد | الغرض |
|---------|--------|
| `MONITOR_PROJECT_NAME` | اسم النظام في API المركزي |
| `MONITOR_CORS_ORIGIN` | CORS للوحة المركزية |
| `MONITOR_API_MIDDLEWARE` | middleware مفصول بفاصلة (مثلاً `throttle:60,1`) |
| `MONITOR_LARAVEL_LOG_PATH` | مسار اللوغات (افتراضي `storage/logs`) |
| `MONITOR_LARAVEL_LOG_PATTERNS` | ملفات مسموحة: `laravel.log,laravel-*.log` |
| `MONITOR_IGNORE_ROUTES` | في config — مسارات لا تُسجّل |
| `MONITOR_RETENTION_DAYS` | حذف JSONL القديم |

### إذا المشروع يستخدم `type_id` للأدمن

اترك `MONITOR_API_MIDDLEWARE` فارغاً للوصول العام، أو أضف middleware مخصص لاحقاً.

### إذا تريد حماية API

```env
MONITOR_API_MIDDLEWARE=throttle:120,1
# أو middleware مخصص من مشروعك
```

---

## بنية الملفات

```
app/Monitor/
  Console/CleanMonitorLogsCommand.php
  Http/Controllers/MonitorApiController.php
  Http/Controllers/DashboardController.php
  Http/Middleware/MonitorRequests.php
  Listeners/...
  Providers/MonitorServiceProvider.php
  Services/
    JsonLineWriter.php
    LogReader.php
    LaravelLogReader.php      ← Laravel log parser
    MetricsAggregator.php
    DbStatusService.php
    ...
config/monitor.php
resources/views/monitor/dashboard.blade.php
storage/logs/monitor/         ← JSONL telemetry
storage/logs/laravel*.log       ← يُقرأ عبر API
```

---

## صيانة

```bash
php artisan monitor:clean
php artisan monitor:clean --days=14
```

---

## أمان

- API **عام افتراضياً** لتسهيل الربط المركزي
- قيّد الوصول عبر VPN / firewall / IP داخلي في الإنتاج
- Laravel logs قد تحتوي بيانات حساسة — لا تعرّضها للإنترنت العام
- المسارات محمية من path traversal — فقط ملفات داخل `MONITOR_LARAVEL_LOG_PATH`

---

## استخراج كـ Composer Package (لاحقاً)

1. انقل `app/Monitor` → `packages/monitoring/src`
2. أضف `composer.json` مع PSR-4 + `extra.laravel.providers`
3. انشر `config/monitor.php` و `dashboard.blade.php`
4. غيّر namespace إن رغبت (`Vendor\Monitoring`)

البنية الحالية جاهزة لهذا الانتقال بأقل تغيير.
