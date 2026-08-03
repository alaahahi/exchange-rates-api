param(
    [Parameter(Mandatory = $true)]
    [string]$Target
)

$ErrorActionPreference = "Stop"
$Source = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path (Join-Path $Source "app\Monitor"))) {
    throw "Monitor module not found in $Source"
}

$paths = @(
    @{ From = "app\Monitor"; To = "app\Monitor" },
    @{ From = "config\monitor.php"; To = "config\monitor.php" },
    @{ From = "resources\views\monitor"; To = "resources\views\monitor" },
    @{ From = "docs\MONITOR_INSTALL.md"; To = "docs\MONITOR_INSTALL.md" },
    @{ From = "scripts\copy-monitor-module.ps1"; To = "scripts\copy-monitor-module.ps1" },
    @{ From = "scripts\copy-monitor-module.sh"; To = "scripts\copy-monitor-module.sh" },
    @{ From = "tests\Unit\Monitor"; To = "tests\Unit\Monitor" },
    @{ From = "tests\Feature\Monitor"; To = "tests\Feature\Monitor" }
)

Write-Host "Source: $Source"
Write-Host "Target: $Target"

foreach ($item in $paths) {
    $from = Join-Path $Source $item.From
    $to = Join-Path $Target $item.To

    if (-not (Test-Path $from)) {
        Write-Warning "Skip missing: $($item.From)"
        continue
    }

    $parent = Split-Path $to -Parent
    if (-not (Test-Path $parent)) {
        New-Item -ItemType Directory -Path $parent -Force | Out-Null
    }

    if (Test-Path $from -PathType Container) {
        Copy-Item -Path $from -Destination $to -Recurse -Force
    } else {
        Copy-Item -Path $from -Destination $to -Force
    }

    Write-Host "Copied $($item.From)"
}

Write-Host ""
Write-Host "Done. Next steps:"
Write-Host "1) Register App\Monitor\Providers\MonitorServiceProvider in config/app.php"
Write-Host "2) Wire ExceptionMonitor in app/Exceptions/Handler.php"
Write-Host "3) Set MONITOR_PROJECT_NAME in .env"
Write-Host "4) php artisan test tests/Unit/Monitor tests/Feature/Monitor"
