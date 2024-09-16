SAOKE2024

## Guide
- Clone the project
- Run `composer install`
- Copy `.env.example` to `.env`
- Run `php artisan key:generate`
- Run `php artisan migrate`
- Run `bun run build` or `npm run build` or whatever you used to build the frontend

## Tools
- Install `pdftotext` by running `brew install poppler` or `sudo apt-get install poppler-utils`

## Vietcombank transaction history (before: 10/09/2024)
- Copy a Vietcombank transaction history file to "database/transactions/" folder
- Run `php artisan vcb:handle {filename}` to process the data transaction

## Vietcombank transaction history (after: [11+12+13+14]/09/2024)
- Copy a Vietcombank transaction history file to "database/transactions/" folder
- Run `php artisan vcb-11092024:handle {filename}` to process the data transaction

## Vietinbank transaction history
- Copy a Vietinbank transaction history file to "database/transactions/" folder
- Run `php artisan vietinbank:handle {filename} --binPath=/usr/local/bin/pdftotext` to process the data transaction

## Notes
- Maybe you should increase the `memory_limit` in `php.ini` to `1024M` or upper if you have a large transaction history filesize