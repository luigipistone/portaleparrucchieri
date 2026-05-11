# Liquid Barber

Web app PHP, JavaScript e MySQL per la gestione di un salone di parrucchieri da uomo.

## Funzionalità

- Frontend pubblico in stile liquid/glass design con animazioni, font moderni e UI responsive.
- Richiesta appuntamento per utenti registrati e ospiti.
- Conferma, annullamento e gestione note da backoffice admin.
- Registrazione/login clienti con dashboard personale.
- CRUD servizi dal pannello admin.
- Vista calendario/lista appuntamenti ordinata per data.

## Installazione

1. Importare `schema.sql` in MySQL.
2. Aggiornare le credenziali database in `config.php`.
3. Avviare il server PHP dalla root del progetto:

```bash
php -S 127.0.0.1:8000
```

Credenziali admin iniziali:

- Email: `admin@example.com`
- Password: `admin123`

> Cambiare la password admin al primo utilizzo in un ambiente reale.
