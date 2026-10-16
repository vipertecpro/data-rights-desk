<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;
use Vipertecpro\DataRightsDesk\Support\AppProfile;

class InstallCommand extends Command
{
    protected $signature = 'data-rights:install';

    protected $description = 'Publish the Data Rights Desk config and migrations';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'data-rights-config']);
        $this->call('vendor:publish', ['--tag' => 'data-rights-migrations']);

        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. php artisan migrate');
        $this->line('  2. Add the HasDataRights trait to your user model.');
        $this->line('  3. Set DATA_RIGHTS_CONTACT_EMAIL (and the other contact keys) in .env.');
        $this->line('  4. Replace the example purposes in config/data-rights.php with your own.');
        $this->line('  5. php artisan data-rights:notice to publish the notice.');
        $this->line('  6. Define the viewDataRightsDesk gate to open the dashboard outside local.');
        $this->line('  7. php artisan data-rights:check');
        $profile = AppProfile::detect(base_path(), (string) config('database.connections.'.config('database.default').'.driver', 'unknown'));

        $this->newLine();
        $this->line('Your app:');
        $this->line('  Database: '.$profile->databaseName().' (the package is tested on SQLite, MySQL, MariaDB, PostgreSQL and SQL Server).');
        $this->line('  Front end: '.$profile->frontEnd.'.');
        $this->line('  Laravel Sanctum: '.($profile->sanctum ? 'installed' : 'not installed').'.');

        $this->newLine();
        $this->line('For your app:');

        if ($profile->isInertia()) {
            $this->line('  - Build your consent and request screens on the JSON feed under /data-rights/api, or link to /data-rights/preferences.');
            $this->line('  - At sign-up, post data_rights[purposes][] and data_rights[notice] with the form, then call DataRights::giveFromForm($user, $request).');
        } else {
            $this->line('  - Put <x-data-rights::consent-fields /> in your sign-up form, then call DataRights::giveFromForm($user, $request).');
            $this->line('  - Link to /data-rights (your rights), /data-rights/notice and /data-rights/preferences from your footer or account pages.');
        }

        if ($profile->sanctum) {
            $this->line("  - For a mobile or NativePHP app, set routes.api to ['middleware' => ['api'], 'auth' => 'auth:sanctum'] in config/data-rights.php.");
        } else {
            $this->line('  - For a mobile or NativePHP app later, run php artisan install:api, then set routes.api for token sign-in (see the README).');
        }

        $this->newLine();
        $this->line('The package helps you meet the DPDP Act; it is not legal advice.');

        return self::SUCCESS;
    }
}
