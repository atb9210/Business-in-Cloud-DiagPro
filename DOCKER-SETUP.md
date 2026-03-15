# BusinessCloud - Docker Setup Guide

## Stack Tecnologico

| Layer | Tecnologia |
|-------|-----------|
| Backend | PHP 8.3, Laravel 12, Filament 3, Livewire 3 |
| Frontend | Vite 6, Tailwind CSS 4 |
| Database | MySQL 8.0 |
| Cache/Queue/Session | Redis 7 |
| Web Server | Nginx (Alpine) |
| Process Manager | Supervisor (PHP-FPM + Nginx + Queue Worker) |
| Container | Docker multi-stage build |

## Quick Start

```bash
# 1. Build e avvia tutto
docker compose up -d --build

# 2. Verifica che tutto funzioni
docker compose ps
docker compose logs -f app

# 3. Accedi all'app
# Frontend: http://localhost
# Admin Filament: http://localhost/admin
# Login: admin@businesscloud.it / changeme123!
```

## Architettura Container

```
businesscloud-app        → Nginx + PHP-FPM + Queue Worker (porta 80)
businesscloud-mysql      → MySQL 8.0 (porta 3306)
businesscloud-redis      → Redis 7 (porta 6379)
businesscloud-scheduler  → Laravel Scheduler (cron ogni minuto)
```

## Configurazione

Modifica `.env.docker` per personalizzare. Variabili chiave:

| Variabile | Default | Descrizione |
|-----------|---------|-------------|
| APP_KEY | (auto-generata) | Chiave crittografica Laravel |
| DB_PASSWORD | diagpro_secure_2024 | Password MySQL |
| REDIS_PASSWORD | diagpro_redis_2024 | Password Redis |
| DB_SEED | true | Esegui seeder al primo avvio |
| ADMIN_EMAIL | admin@businesscloud.it | Email utente admin |
| ADMIN_PASSWORD | changeme123! | Password admin (CAMBIALA!) |

## Comandi Utili

```bash
# Ricostruisci immagine dopo modifiche al codice
docker compose up -d --build app

# Logs in tempo reale
docker compose logs -f app

# Esegui artisan nel container
docker compose exec app php artisan migrate:status
docker compose exec app php artisan tinker

# Reset completo (ATTENZIONE: cancella dati)
docker compose down -v
docker compose up -d --build
```

## Bug Corretti (rispetto alla versione precedente)

1. **php-fpm.conf**: include path puntava a `php82` invece di `php83`
2. **Utente inconsistente**: mix di `diagpro`, `nginx`, `www-data` → unificato a `www-data`
3. **Redis check nell'entrypoint**: aggiunta autenticazione e gestione errori
4. **Health check**: ora usa `/up` (Laravel nativo) invece di endpoint nginx statico
5. **Queue worker duplicato**: rimosso container `queue` separato (ora in Supervisor)
6. **DatabaseSeeder**: aggiunto utente admin con password esplicita per Filament
7. **Dockerfile**: multi-stage ottimizzato (3 stage: composer, node, production)
8. **OPcache**: abilitato per performance in produzione
