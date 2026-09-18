# HumanClinica — pannello prenotazioni (PHP)

Pannello di backend leggero (PHP puro, nessun framework) per gestire le
prenotazioni salvate da `humanclinica.aspx` nella tabella `tReservations`.
Login sulla tabella `tUsers`, calendario con filtro per data, e azioni per
ogni prenotazione (elimina, presa in carico, WhatsApp, messaggio
personalizzato, modifica). Include anche la pagina "Messaggi" per gestire i
modelli di messaggio e le impostazioni SMTP.

## Importante: come sono stati ricostruiti nomi di tabelle/colonne

Non è stato possibile collegarsi al database reale di produzione da questa
sessione (nessuna credenziale/connettività). I nomi di tabelle e colonne in
`config/schema.php` sono stati ricostruiti analizzando le stringhe di testo
incluse nel backup `humanClinica.bak` (metadati di sistema e testo di
stored procedure salvati in chiaro nel backup). La confidenza è alta per
`tUsers.username`, `tUsers.Password`, `tReservations`, `tMessages`
(name/type/lang/value — trovati come frammenti letterali di query SQL), media
per il resto (es. `day`, `beatyadvisor`, `link-meet`).

**Prima di andare in produzione**, dopo aver compilato `config/config.php`:

```bash
php tools/check_schema.php
```

Questo script confronta `config/schema.php` con le colonne reali
(`INFORMATION_SCHEMA.COLUMNS`) e segnala eventuali nomi sbagliati da
correggere — tutto il resto dell'app legge i nomi di colonna solo da quel
file, quindi basta sistemare un punto solo.

## Password: perché il login usa uno schema nuovo (bcrypt)

La password di esempio fornita (`delpiero1980` → `UN346RxmVbyJj+TrCDdzTg==`)
decodifica in 16 byte grezzi: non è un hash MD5/SHA (verificato), ma il
risultato tipico di una cifratura simmetrica reversibile (es. AES o
TripleDES) con una chiave segreta scritta nel codice C# dell'app ASP.NET.
Da una singola coppia chiaro/cifrato non è recuperabile la chiave (è lo scopo
della cifratura) senza il codice sorgente.

Per questo il pannello PHP **non tocca** la colonna `Password` esistente
(continua a funzionare invariata per l'app ASP.NET) e usa una nuova colonna
`PasswordPHP` con hash bcrypt, indipendente.

Per impostare/reimpostare la password di un utente esistente nel pannello PHP:

```bash
php tools/set_password.php <username> <nuova-password>
```

Esegui prima la migrazione che aggiunge la colonna (una tantum):

```sql
-- vedi db/migrations/001_add_password_hash_column.sql
```

## Requisiti

- PHP 8.1+ con estensione **pdo_sqlsrv** (Microsoft Drivers for PHP for SQL
  Server — tipico su hosting Windows/IIS, spesso già presente se gira anche
  l'app ASP.NET) oppure **pdo_dblib** (FreeTDS, hosting Linux). Il driver si
  sceglie in `config/config.php` (`db.driver`).
  Se la versione di PHP è più recente dell'ultima release ufficiale di
  `pdo_sqlsrv` (capita con versioni di PHP appena uscite — verifica con
  `php -m | grep sqlsrv`), usa `db.driver = 'odbc'`: passa dal driver ODBC di
  sistema (**ODBC Driver 17/18 for SQL Server**, da installare a parte se non
  già presente) tramite `PDO_ODBC`, che di solito è già incluso nelle build
  ufficiali di PHP per Windows e non dipende dalla versione di PHP.
- Nessuna dipendenza esterna: niente Composer, niente librerie da installare
  (l'invio email usa un client SMTP scritto internamente in `src/SmtpMailer.php`,
  il calendario usa FullCalendar via CDN).

## Installazione

1. Copia `config/config.sample.php` in `config/config.php` e compila i dati
   di connessione al database (`config.php` è escluso da git, non committare
   credenziali reali).
2. Esegui la migrazione SQL `db/migrations/001_add_password_hash_column.sql`
   sul database.
3. Verifica la mappatura schema:
   ```bash
   php tools/check_schema.php
   ```
   correggi `config/schema.php` se necessario.
4. Imposta la password del primo utente (es. l'admin già esistente in
   `tUsers`):
   ```bash
   php tools/set_password.php <username-esistente> <nuova-password>
   ```
5. Pubblica la cartella `public/` come document root del sito (consigliato).
   Se l'hosting non permette di cambiare document root, pubblica l'intera
   cartella: `config/`, `src/`, `db/`, `tools/` hanno già regole
   `.htaccess`/`web.config` che ne negano l'accesso diretto via browser
   (funzionano su Apache e IIS).
6. Apri `login.php` (o `index.php`, che reindirizza automaticamente).

## Deploy automatico da GitHub (server IIS dedicato)

Se hai un server IIS dedicato con pieno controllo, `.github/workflows/deploy.yml`
automatizza il deploy: ad ogni push su `main` un **GitHub Actions self-hosted
runner** installato sul server sincronizza i file nella cartella del sito.
Nessuna porta da aprire, nessuna credenziale FTP/SSH da gestire da GitHub:
il runner gira sul server e va lui stesso a "tirare" gli aggiornamenti.

### Setup una tantum

1. **Scegli la cartella di deploy sul server**, es. `D:\sites\humanclinica-admin\`.
   L'intero repository (non solo `public/`) va sincronizzato lì, perché il
   codice in `public/` referenzia `../src`, `../config`, ecc. con percorsi
   relativi — la struttura del repo va mantenuta intera su disco.

2. **Imposta il sito IIS** con physical path
   `D:\sites\humanclinica-admin\public` (così `config/`, `src/`, `db/`,
   `tools/` restano fuori dalla cartella servita da IIS, mai raggiungibili
   via browser).

3. **Crea manualmente `config\config.php`** in
   `D:\sites\humanclinica-admin\config\config.php` (copiando
   `config.sample.php` e compilandolo con le credenziali reali). Il deploy
   automatico non lo tocca mai (è escluso esplicitamente in
   `deploy/deploy.ps1`), quindi va creato una sola volta.

4. **Installa il self-hosted runner sul server**: nel repo GitHub vai su
   *Settings → Actions → Runners → New self-hosted runner*, scegli Windows e
   segui i comandi mostrati (download, `config.cmd` con il token generato lì
   — cambia ad ogni registrazione, va preso al momento). Quando richiesto,
   assegna al runner (oltre alle label di default) anche la label
   `humanclinica`, così il workflow lo seleziona in modo specifico (utile se
   in futuro registri altri runner sullo stesso server per altri siti, come
   Footgolf Italia).
   Installalo come **servizio Windows** (`.\svc install` poi `.\svc start`
   dentro la cartella del runner) così riparte da solo dopo un riavvio e non
   serve una sessione utente aperta.

5. **Imposta le variabili del repository**: *Settings → Secrets and
   variables → Actions → tab Variables* → aggiungi:
   - `DEPLOY_PATH` = `D:\sites\humanclinica-admin`
   - `IIS_APP_POOL` = nome dell'application pool del sito (opzionale — se
     presente viene riavviato ad ogni deploy; per PHP non è strettamente
     necessario, ma evita cache stantie se usi opcache).

6. **Fai un push su `main`** (o lancia il workflow manualmente dalla tab
   *Actions* → *Deploy to IIS* → *Run workflow*): il runner esegue un
   controllo di sintassi PHP (`php -l` su tutti i file), sincronizza i file
   con `robocopy /MIR` (esclude sempre `.git`, `.github` e `config.php`) ed
   eventualmente riavvia l'application pool.

Da quel momento, ogni volta che fai il merge su `main`, il sito si aggiorna
da solo entro pochi secondi.

### Deploy manuale (senza runner)

Se preferisci lanciare il deploy a mano invece di configurare il runner
automatico, `deploy/manual-deploy.ps1` fa tutto in un colpo: clona/aggiorna
il repository da GitHub e sincronizza la cartella del sito (richiede `git`
e `php` nel PATH del server):

```powershell
.\deploy\manual-deploy.ps1 -Destination "D:\sites\humanclinica-admin"
```

Alla prima esecuzione clona il repo in `C:\deploy-src\humanclinica`
(personalizzabile con `-ClonePath`); alle esecuzioni successive lo aggiorna
con `git fetch` + `git reset --hard` **solo in quella cartella di lavoro**,
mai nella cartella del sito. Aggiungi `-IisAppPool "nome-app-pool"` per
riavviare automaticamente l'application pool a fine deploy.

In alternativa, `deploy/quick-deploy.ps1` segue lo schema "wipe & republish"
(cancella la cartella del sito e la ripubblica da zero ad ogni deploy,
come per altri progetti tipo Orion): richiede che il repo sia già clonato a
mano in `C:\deploy-src\humanclinica` (le due variabili `$RepoPath` e
`$SitePath` in cima allo script vanno adattate ai tuoi percorsi reali).
Salva `config\config.php` da parte prima di cancellare la cartella e lo
rimette a posto subito dopo, perché — a differenza di un output di build —
contiene le credenziali reali del server e non è nel repo:

```powershell
.\deploy\quick-deploy.ps1
```

## Struttura

```
config/             configurazione (config.php, schema.php) — non pubblica
db/migrations/      script SQL una tantum
src/                classi PHP (Database, Auth, Repository, SmtpMailer, ...)
tools/              script da riga di comando (set_password, check_schema)
public/             document root: pagine, assets, endpoint api/*.php
deploy/deploy.ps1   script di sincronizzazione usato dal deploy automatico
.github/workflows/  workflow GitHub Actions (deploy.yml)
```

## Funzionalità

- **Login** (`login.php`) su `tUsers` (username + password bcrypt in
  `PasswordPHP`), sessione PHP, CSRF token su tutte le form/azioni.
- **Calendario** (`dashboard.php`): vista mensile (FullCalendar) con un
  evento colorato per stato per ogni prenotazione; click su un giorno filtra
  la tabella sottostante. Filtri manuali per intervallo di date, stato,
  ricerca testuale.
- **Azioni per prenotazione**:
  - *Elimina* — cancella la riga da `tReservations` (richiede conferma).
  - *Presa in carico* — invia via email il template `PRESA_IN_CARICO` (da
    creare/modificare nella pagina Messaggi) e imposta lo stato su
    `CONTATTATO`.
  - *WhatsApp* — apre `https://wa.me/<numero>` con un messaggio precompilato
    (nessuna integrazione API, semplice link, come richiesto).
  - *Messaggio personalizzato* — form libero (oggetto + testo), inviato via
    email SMTP.
  - *Modifica* — modale per correggere nome, contatti, data/ora, stato,
    beauty advisor assegnato.
- **Messaggi** (`messaggi.php`): elenco/editing dei template salvati in
  `tMessages` (placeholder `@NOME`, `@COGNOME`, `@DATA`, `@ORA`, `@TELEFONO`,
  `@EMAIL`), creazione di nuovi template, e impostazioni SMTP (host, porta,
  cifratura, utente, password, mittente) salvate anch'esse in `tMessages`
  (righe con `TYPE='CONFIG'`) così sono modificabili dalla stessa pagina,
  come nel pannello ASP.NET esistente.

## Note di sicurezza

- Tutte le query usano parametri preparati (PDO) — nessuna concatenazione di
  input utente in SQL.
- Tutte le azioni che modificano dati richiedono un token CSRF valido.
- Le password nel nuovo schema sono hashate con bcrypt (`password_hash`),
  mai salvate in chiaro.
- Le cartelle `config/`, `src/`, `db/`, `tools/` sono protette da accesso
  diretto via browser (vedi sopra); tienile comunque fuori dalla document
  root quando possibile.
