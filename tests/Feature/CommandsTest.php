<?php

use Illuminate\Support\Facades\File;

it('writes a source stub to app/DataRights and refuses to overwrite it', function () {
    $path = app_path('DataRights/Orders.php');
    File::delete($path);

    $this->artisan('data-rights:make-source', ['name' => 'orders'])->expectsOutputToContain('Created')->assertExitCode(0);

    $code = File::get($path);
    expect($code)->toContain('namespace App\DataRights;')
        ->toContain('class Orders implements Exporter, Eraser')
        ->toContain("return 'Orders';")
        ->toContain('TODO');

    $this->artisan('data-rights:make-source', ['name' => 'Orders'])->assertExitCode(1);
    $this->artisan('data-rights:make-source', ['name' => '9lives'])->assertExitCode(1);

    File::deleteDirectory(app_path('DataRights'));
});

it('prints the next steps after install', function () {
    try {
        $this->artisan('data-rights:install')
            ->expectsOutputToContain('php artisan data-rights:notice')
            ->expectsOutputToContain('Your app:')
            ->expectsOutputToContain('Database: SQLite')
            ->expectsOutputToContain('consent-fields')
            ->expectsOutputToContain('not legal advice')
            ->assertExitCode(0);

        expect(File::exists(config_path('data-rights.php')))->toBeTrue()
            ->and(File::glob(database_path('migrations/*create_data_rights_consents_table.php')))->toHaveCount(1);
    } finally {
        // The publish lands in the Testbench skeleton: leave it as it was.
        File::delete(config_path('data-rights.php'));
        File::delete(File::glob(database_path('migrations/*data_rights*')));
    }
});
