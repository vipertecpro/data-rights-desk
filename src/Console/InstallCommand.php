<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Vipertecpro\DataRightsDesk\Support\AppProfile;

class InstallCommand extends Command
{
    protected $signature = 'data-rights:install';

    protected $description = 'Publish the Data Rights Desk config and migrations';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'data-rights-config']);

        // Laravel renames published migrations with today's date, so publishing twice would
        // create the tables twice and break migrate.
        if (File::glob(database_path('migrations/*_create_data_rights_subjects_table.php')) === []) {
            $this->call('vendor:publish', ['--tag' => 'data-rights-migrations']);
        } else {
            $this->line('  The migrations are already in database/migrations, so they were not copied again.');
        }

        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. php artisan migrate');
        $this->line('  2. Add the HasDataRights trait to your user model.');
        $this->line('  3. Set APP_URL to your site\'s address and DATA_RIGHTS_CONTACT_EMAIL (and the other contact keys) in .env.');
        $this->line('  4. Replace the example purposes in config/data-rights.php with your own.');
        $this->line('  5. php artisan data-rights:notice to publish the notice.');
        $this->line('  6. Define the viewDataRightsDesk gate to open the dashboard outside local.');
        $this->line('  7. php artisan data-rights:check');

        $this->warnAboutTheApp();
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

    /** Things in this app that need a setting before the first migrate or the first visit. */
    private function warnAboutTheApp(): void
    {
        $warnings = [];
        $class = config('data-rights.subject_model');
        $user = is_string($class) && class_exists($class) && is_subclass_of($class, Model::class) ? new $class : null;

        if ($user instanceof Model) {
            $traits = class_uses_recursive($user);
            $type = match (true) {
                in_array(HasUuids::class, $traits, true) => 'uuid',
                in_array(HasUlids::class, $traits, true) => 'ulid',
                default => null,
            };

            if ($type !== null && config('data-rights.morph_key_type') !== $type) {
                $warnings[] = "Your user model uses {$type} keys. Set 'morph_key_type' => '{$type}' in config/data-rights.php before you migrate.";
            }

            $column = (string) config('data-rights.subject_email_column', 'email');

            try {
                if (Schema::hasTable($user->getTable()) && ! Schema::hasColumn($user->getTable(), $column)) {
                    $warnings[] = "Your [{$user->getTable()}] table has no [{$column}] column. Set subject_email_column, or the package cannot email people.";
                }
            } catch (Throwable) {
                // No database yet: nothing to check.
            }
        }

        if (! Route::has('login')) {
            $warnings[] = 'Your app has no route named login. Guests who open the consent or request pages would get an error: call $middleware->redirectGuestsTo(...) in bootstrap/app.php.';
        }

        if ($warnings === []) {
            return;
        }

        $this->newLine();
        $this->line('Before you go on:');

        foreach ($warnings as $warning) {
            $this->warn('  - '.$warning);
        }
    }
}
