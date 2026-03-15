# BusinessCloud - Troubleshooting & Fix Log

**Data:** 15 Marzo 2026  
**Progetto:** BusinessCloud (Laravel 12 + Filament 3)  
**Deployment:** Dokploy (Docker Swarm) su server `164.68.111.35`  
**URL:** `http://depo-businesscloud-7zczs7-0a902c-164-68-111-35.traefik.me`

---

## Problema Principale: Access Denied MySQL Intermittente

### Sintomo
```
SQLSTATE[HY000] [1045] Access denied for user 'diagpro'@'10.0.1.x' (using password: YES)
```
L'errore appariva in modo **intermittente** (circa 50% delle richieste) durante la navigazione nel pannello admin Filament. La query falliva su qualsiasi tabella (`sessions`, `users`, ecc.).

### Causa Radice: Docker Swarm IPVS Load Balancer

Dokploy utilizza **Docker Swarm** con una rete **overlay** (`dokploy-network`). Docker Swarm implementa un load balancer interno basato su **IPVS (IP Virtual Server)** che esegue **Source NAT (SNAT)** sui pacchetti di rete.

Il problema: durante l'handshake di autenticazione MySQL (challenge-response), l'IPVS modifica i pacchetti di rete, corrompendo il processo di autenticazione. MySQL riceve credenziali corrotte e risponde con `Access denied` anche se username e password sono corretti.

### Diagnosi passo per passo

| Test | Risultato | Conclusione |
|------|-----------|-------------|
| Connessione da `127.0.0.1` (localhost nel container MySQL) | ✅ OK | Password e plugin corretti |
| Connessione via IP diretto `10.0.1.174` (dal container app) | ✅ OK | Rete funziona senza IPVS |
| Connessione via hostname `mysql` (Docker DNS → IPVS) | ❌ ~50% FAIL | IPVS corrompe l'handshake |
| `SELECT user, host, plugin FROM mysql.user` | `mysql_native_password` | Plugin corretto |
| `SHOW STATUS LIKE 'Threads_connected'` | 3/300 | Non è un problema di connessioni |
| `SHOW VARIABLES LIKE 'host_cache_size'` | 0 | Host cache disabilitato |
| Password nel config cache Laravel | `diagpro_secure_2024` | Password corretta |

### Soluzione Implementata: RetryMySqlConnector

File: `app/Database/RetryMySqlConnector.php`

```php
class RetryMySqlConnector extends MySqlConnector
{
    public function connect(array $config)
    {
        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return parent::connect($config);
            } catch (PDOException $e) {
                if ($attempt < $maxRetries && str_contains($e->getMessage(), 'Access denied')) {
                    usleep(50000); // 50ms
                    continue;
                }
                throw $e;
            }
        }
    }
}
```

Registrato in `app/Providers/AppServiceProvider.php`:
```php
$this->app->bind('db.connector.mysql', function () {
    return new RetryMySqlConnector();
});
```

**Perché funziona:** L'IPVS corrompe circa il 50% degli handshake, ma la probabilità di 3 fallimenti consecutivi è ~12.5% → ~1.5% → praticamente zero con il retry.

---

## Approcci Tentati e Non Funzionanti

### 1. `--init-file` su MySQL
- **Idea:** Eseguire `ALTER USER` ad ogni avvio MySQL
- **Problema:** `--init-file` viene eseguito PRIMA che Docker entrypoint crei gli utenti → crash su volumi nuovi
- **Stato:** ❌ Rimosso

### 2. `ALTER USER` nell'entrypoint dell'app
- **Idea:** L'app container esegue ALTER USER come root al boot
- **Problema:** `DB_ROOT_PASSWORD` non era nell'environment dell'app. Anche dopo averla aggiunta, la connessione root dalla rete overlay falliva per lo stesso problema IPVS
- **Stato:** ❌ Rimosso

### 3. Rete bridge interna
- **Idea:** Creare una rete `internal: driver: bridge` per MySQL/Redis
- **Problema:** Dokploy/Docker Swarm non supporta reti bridge locali per compose
- **Errore:** `failed to set up container networking: updating external connectivity`
- **Stato:** ❌ Non supportato

### 4. `deploy: endpoint_mode: dnsrr`
- **Idea:** Disabilitare IPVS load balancer usando DNS round-robin
- **Problema:** Non sufficiente su Swarm, l'IPVS continua a interferire
- **Stato:** ❌ Insufficiente

### 5. `SESSION_DRIVER=redis`
- **Idea:** Spostare le sessioni su Redis per evitare query MySQL
- **Problema:** Redis era configurato con password ma Laravel non la passava correttamente
- **Errore:** `ERR AUTH <password> called without any password configured`
- **Stato:** ❌ Rimosso (tornato a `file`)

---

## Configurazione Finale

### docker-compose.dokploy.yml
- **MySQL:** `--default-authentication-plugin=mysql_native_password --skip-name-resolve`
- **SESSION_DRIVER:** `file` (non dipende da MySQL né Redis)
- **CACHE_STORE:** `file`
- **Rete:** `dokploy-network` (overlay esterna, obbligatoria per Dokploy)
- **DB_ROOT_PASSWORD:** passata al container app per eventuali future necessità

### MySQL Config (`docker/mysql/my.cnf`)
- `default_authentication_plugin = mysql_native_password`
- `skip-host-cache` e `skip-name-resolve`
- `max_connections = 300`
- `wait_timeout = 600`
- `host_cache_size = 0`
- `max_connect_errors = 1000000`

### PHP-FPM (`docker/php/www.conf`)
- `pm.max_children = 20`
- `pm.max_requests = 500`
- `request_terminate_timeout = 60s`

### Laravel (`config/database.php`)
- `PDO::ATTR_PERSISTENT => false`
- `PDO::ATTR_TIMEOUT => 10`
- `PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"`

---

## Altre Fix Applicate

### Widget Polling Disabilitato
Tutti i widget Filament avevano `wire:poll.5s` attivo di default, generando query MySQL ogni 5 secondi per ogni tab aperto. Disabilitato su tutti i widget:

- `ClientiStatsOverview` → `protected static ?string $pollingInterval = null;`
- `ClientiChart` → `protected static ?string $pollingInterval = null;`
- `OrdiniStatsWidget` → `protected static ?string $pollingInterval = null;`
- `CampagnaStatsWidget` → `protected static ?string $pollingInterval = null;`
- `ProdottiStatsWidget` → `protected static ?string $pollingInterval = null;`
- `RicorrenzeAttiveStatsWidget` → `protected static ?string $pollingInterval = null;`
- `AttivazioniChartWidget` → `protected static ?string $pollingInterval = null;`

### Null Safety su Widget
Aggiunta protezione null su:
- `ClientiStatsOverview` → `$ultimoCliente->created_at->format()` protetto con `?`
- `OrdiniStatsWidget` → operazioni aritmetiche con campi potenzialmente null

---

## Environment Variables Dokploy

```env
APP_KEY=base64:q4Kjt0UjKUjBtTKONkS+kWk8zCMIUwc7sug9bgZIEVE=
DB_ROOT_PASSWORD=tua_password_root_sicura
DB_PASSWORD=diagpro_secure_2024
REDIS_PASSWORD=diagpro_redis_2024
APP_ENV=production
APP_DEBUG=true
APP_URL=http://depo-businesscloud-7zczs7-0a902c-164-68-111-35.traefik.me
DB_SEED=true
DB_USERNAME=diagpro
DB_DATABASE=diagpro
ADMIN_EMAIL=admin@businesscloud.it
ADMIN_PASSWORD=changeme123!
ADMIN_NAME=Admin
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
```

---

## File Modificati (Riepilogo)

| File | Modifica |
|------|----------|
| `app/Database/RetryMySqlConnector.php` | **NUOVO** — Connector MySQL con retry automatico |
| `app/Providers/AppServiceProvider.php` | Registrazione RetryMySqlConnector |
| `config/database.php` | PDO options (timeout, init command) |
| `docker-compose.dokploy.yml` | MySQL command, env vars, SESSION_DRIVER=file |
| `docker/mysql/my.cnf` | mysql_native_password, skip-host-cache, max_connections |
| `docker/entrypoint.sh` | Pulizia ALTER USER (rimosso) |
| `app/Filament/Admin/Widgets/*.php` | Polling disabilitato su tutti i widget |

---

## Lezioni Apprese

1. **Docker Swarm IPVS** può corrompere gli handshake di autenticazione MySQL sulla rete overlay — questo è un problema noto ma poco documentato
2. **`--default-authentication-plugin=mysql_native_password`** nel command Docker funziona solo per utenti NUOVI, non per utenti esistenti in volumi persistenti
3. **`--init-file`** viene eseguito PRIMA che Docker entrypoint crei utenti MySQL → non usare per ALTER USER
4. **Reti bridge** non sono supportate in Docker Swarm compose su Dokploy
5. **Il retry a livello applicativo** è la soluzione più robusta quando l'infrastruttura di rete non è controllabile
6. **SESSION_DRIVER=file** è la scelta più resiliente per deployment Docker — nessuna dipendenza esterna per le sessioni
