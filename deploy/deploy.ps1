#Requires -Version 5.1
<#
.SYNOPSIS
    Sync the checked-out repository into the live site folder on the IIS server.

.DESCRIPTION
    Mirrors $Source into $Destination with robocopy, WITHOUT ever touching:
      - config/config.php   (server-only DB/SMTP credentials, not in git)
      - .git, .github        (repo/CI metadata, not needed on the server)

    Safe to run repeatedly. Exit code is 0 on success, non-zero on failure
    (robocopy codes 8+ mean at least one file failed to copy).

.PARAMETER Source
    Path to the checked-out repository (e.g. $env:GITHUB_WORKSPACE).

.PARAMETER Destination
    Path to the live folder on the server, e.g. D:\sites\humanclinica-admin.
    The IIS site's physical path should point at "$Destination\public".
#>
param(
    [Parameter(Mandatory = $true)][string]$Source,
    [Parameter(Mandatory = $true)][string]$Destination
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path $Source)) {
    throw "Cartella sorgente non trovata: $Source"
}

if (-not (Test-Path $Destination)) {
    Write-Host "Creo la cartella di destinazione: $Destination"
    New-Item -ItemType Directory -Path $Destination -Force | Out-Null
}

Write-Host "Sincronizzo $Source -> $Destination"

robocopy $Source $Destination /MIR `
    /XD ".git" ".github" `
    /XF "config.php" `
    /NFL /NDL /NP /R:2 /W:5

# Robocopy exit codes: 0-7 = success (various combinations of copied/skipped
# files, no failures). 8+ = at least one file failed to copy. See:
# https://learn.microsoft.com/troubleshoot/windows-server/backup-and-storage/return-codes-used-robocopy-utility
$exitCode = $LASTEXITCODE

if ($exitCode -ge 8) {
    throw "Robocopy ha restituito il codice $exitCode (fallimento). Controlla il log sopra."
}

Write-Host "Deploy completato (robocopy exit code $exitCode)."

$configPath = Join-Path $Destination 'config\config.php'
if (-not (Test-Path $configPath)) {
    Write-Warning "config\config.php non esiste in $Destination. Al primo deploy va creato manualmente una tantum (copia da config.sample.php e compila con le credenziali reali)."
}

exit 0
