# Deploy manuale "wipe & republish", stesso pattern usato per Orion:
# cd nella copia di lavoro locale, aggiorna da GitHub, svuota la cartella
# del sito e la ripubblica da zero.
#
# A differenza di un output di build (dotnet publish, ecc.), la cartella del
# sito PHP contiene anche config\config.php: le credenziali reali del
# database/SMTP, che vivono SOLO sul server e non sono nel repo. Per questo,
# a differenza dello script di Orion, qui il file viene salvato da parte
# prima di svuotare la cartella e rimesso a posto subito dopo.
#
# Uso: apri PowerShell sul server, modifica se necessario le variabili
# qui sotto, poi esegui questo script (o incollane il contenuto a mano).

$RepoUrl  = "https://github.com/delphi131/HUMANCLINICA.git"
$RepoPath = "C:\humanclinica-src"                              # copia di lavoro locale del repo
# Cartella di deploy del pannello: deve corrispondere ESATTAMENTE al Physical
# Path configurato per la IIS Application "admin" (meno "\public", che ci
# pensa deploy.ps1 ad aggiungerlo) — è la stessa cartella dove hai creato
# config\config.php a mano.
$SitePath = "C:\iss-Site\app.humanclinica.it\admin"

$ErrorActionPreference = "Stop"

# Clona il repository se non è già presente (prima esecuzione), invece di
# fallire silenziosamente più avanti.
if (-not (Test-Path (Join-Path $RepoPath ".git"))) {
    Write-Host "Repository non trovato in $RepoPath, lo clono..." -ForegroundColor Cyan
    git clone $RepoUrl $RepoPath
    if ($LASTEXITCODE -ne 0) { throw "git clone fallito (codice $LASTEXITCODE)." }
}

Set-Location $RepoPath

# I comandi esterni (git, robocopy, ...) non generano errori "terminanti"
# per PowerShell: $ErrorActionPreference non basta, va controllato
# $LASTEXITCODE dopo ognuno, altrimenti lo script continua anche se il
# comando è fallito.
git checkout main
if ($LASTEXITCODE -ne 0) { throw "git checkout main fallito (codice $LASTEXITCODE)." }

git pull
if ($LASTEXITCODE -ne 0) { throw "git pull fallito (codice $LASTEXITCODE)." }

if (-not (Test-Path (Join-Path $RepoPath "deploy\deploy.ps1"))) {
    throw "deploy\deploy.ps1 non trovato in $RepoPath dopo il pull: controlla che $RepoPath sia davvero il repo HUMANCLINICA."
}

# PHP non ha una fase di build (niente obj/bin da pulire come per Orion).
# L'unico stato da non perdere nella cartella di destinazione è
# config\config.php: lo salviamo da parte prima di svuotare tutto.
$configPath   = Join-Path $SitePath "config\config.php"
$configBackup = "$env:TEMP\humanclinica-config.php.bak"
if (Test-Path $configPath) {
    Copy-Item $configPath $configBackup -Force
}

# Cartella di destinazione: la si cancella prima, così i file pubblicati sono
# sempre esattamente quelli del branch corrente, senza residui di deploy
# precedenti.
Remove-Item -Recurse -Force $SitePath -ErrorAction SilentlyContinue

.\deploy\deploy.ps1 -Source $RepoPath -Destination $SitePath

if (Test-Path $configBackup) {
    New-Item -ItemType Directory -Force -Path (Split-Path $configPath) | Out-Null
    Copy-Item $configBackup $configPath -Force
    Remove-Item $configBackup -Force
    Write-Host "config\config.php ripristinato." -ForegroundColor Cyan
} else {
    Write-Warning "config\config.php non trovato: se e' il primo deploy, creane uno copiando config.sample.php."
}

Write-Host "Deploy completato." -ForegroundColor Green
