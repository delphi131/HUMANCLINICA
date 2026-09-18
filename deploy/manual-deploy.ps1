#Requires -Version 5.1
<#
.SYNOPSIS
    Clona/aggiorna il repository da GitHub e pubblica i file nella cartella
    del sito IIS. Pensato per essere lanciato a mano da PowerShell sul server
    (alternativa/complemento al deploy automatico di .github/workflows/deploy.yml).

.PARAMETER RepoUrl
    URL del repository GitHub.

.PARAMETER Branch
    Branch da pubblicare (default: main).

.PARAMETER ClonePath
    Cartella di lavoro locale dove tenere il checkout del repository
    (NON è la cartella del sito: è solo una copia di lavoro intermedia).

.PARAMETER Destination
    Cartella live sul server, es. D:\sites\humanclinica-admin. Il sito IIS
    va puntato su "$Destination\public".

.PARAMETER IisAppPool
    Nome dell'application pool IIS da riavviare dopo il deploy (opzionale).

.EXAMPLE
    .\manual-deploy.ps1 -Destination "D:\sites\humanclinica-admin"

.EXAMPLE
    .\manual-deploy.ps1 -Destination "D:\sites\humanclinica-admin" -IisAppPool "humanclinica-admin"
#>
param(
    [string]$RepoUrl = "https://github.com/delphi131/HUMANCLINICA.git",
    [string]$Branch = "main",
    [string]$ClonePath = "C:\deploy-src\humanclinica",
    [Parameter(Mandatory = $true)][string]$Destination,
    [string]$IisAppPool = ""
)

$ErrorActionPreference = "Stop"

function Assert-Command {
    param([string]$Name)
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Comando '$Name' non trovato nel PATH. Installalo prima di continuare (git: https://git-scm.com/download/win)."
    }
}

Assert-Command git
Assert-Command php

# 1. Clona il repository (prima volta) oppure lo aggiorna alla versione
#    esatta del branch remoto (git reset --hard tocca SOLO la cartella di
#    lavoro $ClonePath, mai la cartella del sito live).
if (-not (Test-Path $ClonePath)) {
    Write-Host "Clono $RepoUrl in $ClonePath ..." -ForegroundColor Cyan
    git clone --branch $Branch $RepoUrl $ClonePath
    if ($LASTEXITCODE -ne 0) { throw "git clone fallito." }
} else {
    Write-Host "Aggiorno il repository in $ClonePath ..." -ForegroundColor Cyan
    Push-Location $ClonePath
    try {
        git fetch origin $Branch
        if ($LASTEXITCODE -ne 0) { throw "git fetch fallito." }
        git checkout $Branch
        if ($LASTEXITCODE -ne 0) { throw "git checkout fallito." }
        git reset --hard "origin/$Branch"
        if ($LASTEXITCODE -ne 0) { throw "git reset fallito." }
    } finally {
        Pop-Location
    }
}

# 2. Controllo sintassi PHP prima di pubblicare, per non mandare online un
#    file rotto.
Write-Host "Controllo sintassi PHP ..." -ForegroundColor Cyan
$failed = $false
Get-ChildItem -Path $ClonePath -Recurse -Filter *.php | ForEach-Object {
    $out = php -l $_.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host $out -ForegroundColor Red
        $failed = $true
    }
}
if ($failed) {
    throw "Trovati errori di sintassi PHP: deploy annullato."
}

# 3. Sincronizza i file nella cartella del sito (riusa deploy.ps1, che esclude
#    sempre .git, .github e config\config.php).
Write-Host "Sincronizzo verso $Destination ..." -ForegroundColor Cyan
& (Join-Path $ClonePath "deploy\deploy.ps1") -Source $ClonePath -Destination $Destination

# 4. Ricicla l'app pool IIS, se indicato (richiede PowerShell come Amministratore).
if ($IisAppPool) {
    Import-Module WebAdministration
    Restart-WebAppPool -Name $IisAppPool
    Write-Host "App pool '$IisAppPool' riavviato." -ForegroundColor Cyan
}

Write-Host "Deploy completato con successo." -ForegroundColor Green
