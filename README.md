SAOKE2024

## Guide
- Clone the project
- Run `composer install`
- Copy `.env.example` to `.env`
- Run `php artisan key:generate`
- Run `php artisan migrate`
- Run `bun run build` or `npm run build` or whatever you used to build the frontend

## Vietcombank transaction history
- Copy a Vietcombank transaction history file to "database/transactions/" folder
- Run `php artisan vcb:handle {filename}` to process the data transaction

## Vietinbank transaction history
- Install `pdftotext` by running `brew install poppler` or `sudo apt-get install poppler-utils`
- Copy a Vietinbank transaction history file to "database/transactions/" folder
- Run `php artisan vietinbank:handle {filename} --binPath=/usr/local/bin/pdftotext` to process the data transaction