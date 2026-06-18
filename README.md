# Gazifin

Jednoduchá fakturační aplikace postavená na Laravel, SQLite a Docker.

## Funkce

- Přihlášení (e-mail/heslo + Google OAuth)
- Správa klientů a faktur
- Plátce / neplátce DPH
- Generování PDF faktur
- QR platba (SPAYD)
- Odeslání faktury přes Gmail API

## Vývoj (Laravel Sail)

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

Aplikace běží na http://localhost

## Produkční Docker image

```bash
cp docker/.env.build.example docker/.env.build
./docker/scripts/build-and-push.sh
```

## Google OAuth

1. Vytvořte OAuth Client ID v Google Cloud Console
2. Povolte Gmail API
3. Nastavte redirect URI: `http://localhost/auth/google/callback`
4. Vyplňte `GOOGLE_CLIENT_ID` a `GOOGLE_CLIENT_SECRET` v `.env`
