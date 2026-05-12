# Liquid Barber

Web app PHP, JavaScript e MySQL per la gestione di un salone di parrucchieri da uomo.

## Funzionalità

- Frontend pubblico in stile liquid/glass design con animazioni, font moderni e UI responsive.
- Richiesta appuntamento per utenti registrati e ospiti.
- Conferma, annullamento e gestione note da backoffice admin.
- Registrazione/login clienti con dashboard personale, modifica profilo e fidelity card.
- Backend admin strutturato con menu, pagine separate e dashboard KPI dettagliata.
- CRUD servizi dal pannello admin.
- Vista calendario/lista appuntamenti ordinata per data.
- Azioni principali in pagina senza reload grazie a richieste AJAX progressive.
- Consultazione prenotazioni guest tramite codice numerico ricevuto via email, email/telefono e QR code.

## Installazione locale

1. Creare/importare il database applicando le migrations SQL in ordine numerico:

```bash
mysql -u root -p < migrations/001_create_core_tables.sql
mysql -u root -p < migrations/002_seed_initial_data.sql
mysql -u root -p < migrations/003_add_booking_token_to_appointments.sql
mysql -u root -p < migrations/004_add_completed_appointment_status.sql
```

2. Copiare `config.local.php.example` in `config.local.php` e inserire le credenziali database reali. Il file `config.local.php` è escluso da Git per non salvare password nel repository.
3. In alternativa, impostare le variabili ambiente `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` e `APP_NAME`.
4. Avviare il server PHP dalla root del progetto:

```bash
php -S 127.0.0.1:8000
```

Credenziali admin iniziali:

- Email: `admin@example.com`
- Password: `admin123`

> Cambiare la password admin al primo utilizzo in un ambiente reale.

## Database e migrations

Le modifiche al database vanno aggiunte come nuovi file in `migrations/`, usando prefissi progressivi (`003_...sql`, `004_...sql`, ecc.).

`schema.sql` resta disponibile solo come snapshot iniziale di bootstrap. Ogni modifica successiva al database deve essere consegnata come nuova migration in `migrations/` e applicata in ordine numerico.

## Deploy

Il deploy sarà gestito tramite GitHub: non sono inclusi script FTP o credenziali di pubblicazione nel repository. Le credenziali database devono restare in `config.local.php` sul server o in variabili ambiente.
