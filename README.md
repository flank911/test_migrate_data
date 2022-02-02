## Installation
- `git clone`
- `composer install`
- `cp .env.example .env`
- Fill `.env` with database connection data
- `php artisan storage:link`
- `php artisan migrate`
- `php artisan db:seed --class=CountriesSeeder`

## Run
- `php artisan csv:migrate_data random.csv`

Report file will be placed at public directory

