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
# Uso: apri PowerShell sul server, modifica se necessario le due variabili
# qui sotto, poi esegui questo script (o incollane il contenuto a mano).

$RepoPath    = "C:\deploy-src\humanclinica"      # copia di lavoro locale del repo
$SitePath    = "D:\sites\humanclinica-admin"     # cartella del sito live (IIS punta a "$SitePath\public")

$ErrorActionPreference = "Stop"

cd $RepoPath
git checkout main
git pull

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
