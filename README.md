Setting up the project: 

- `git clone git@github.com:nahapet93/elastic-backups.git`
- `composer i`
- `npm i`
- `cp .env.example .env`
- `php artisan migrate:fresh`
- `php artisan make:filament-user`
- `php artisan key:generate`
- `php artisan storage:link`

For testing locally:

- `php artisan serve`
- `php artisan horizon`
- `php artisan queue:work`
- `php artisan schedule:work`
