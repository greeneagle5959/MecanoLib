param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$SymfonyArgs
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Normalize-PathString {
    param([string]$PathValue)

    if ([string]::IsNullOrWhiteSpace($PathValue)) {
        return $null
    }

    return ([System.IO.Path]::GetFullPath($PathValue)).TrimEnd('\\')
}

function Remove-StaleSymfonyPidFiles {
    param([string]$TargetProjectDir)

    $symfonyVarDir = Join-Path $HOME '.symfony5\var'
    if (-not (Test-Path $symfonyVarDir)) {
        return
    }

    $normalizedProjectDir = Normalize-PathString $TargetProjectDir

    foreach ($pidFile in Get-ChildItem -Path $symfonyVarDir -Filter '*.pid' -Recurse -File -ErrorAction SilentlyContinue) {
        try {
            $data = Get-Content $pidFile.FullName -Raw | ConvertFrom-Json
            if (-not $data.PSObject.Properties.Name.Contains('dir')) {
                continue
            }

            $pidProjectDir = Normalize-PathString ([string]$data.dir)
            if ($pidProjectDir -ne $normalizedProjectDir) {
                continue
            }

            $pidValue = 0
            [void][int]::TryParse([string]$data.pid, [ref]$pidValue)

            $proc = if ($pidValue -gt 0) {
                Get-CimInstance Win32_Process -Filter "ProcessId=$pidValue" -ErrorAction SilentlyContinue
            } else {
                $null
            }

            $isLivePhpCgi = $null -ne $proc -and $proc.Name -ieq 'php-cgi.exe'

            if (-not $isLivePhpCgi) {
                Remove-Item $pidFile.FullName -Force -ErrorAction SilentlyContinue
            }
        } catch {
            Remove-Item $pidFile.FullName -Force -ErrorAction SilentlyContinue
        }
    }
}

$projectDir = Normalize-PathString (Get-Location).Path
Remove-StaleSymfonyPidFiles -TargetProjectDir $projectDir

if (-not $SymfonyArgs -or $SymfonyArgs.Count -eq 0) {
    $SymfonyArgs = @('server:start')
}

& symfony.exe @SymfonyArgs
exit $LASTEXITCODE
