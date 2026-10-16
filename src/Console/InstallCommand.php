<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;

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
        $this->newLine();
        $this->line('The package helps you meet the DPDP Act; it is not legal advice.');

        return self::SUCCESS;
    }
}
