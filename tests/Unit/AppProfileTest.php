<?php

use Vipertecpro\DataRightsDesk\Support\AppProfile;

function appWith(array $composer, array $node = []): string
{
    $dir = sys_get_temp_dir().'/drd-profile-'.bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir.'/composer.json', json_encode($composer));

    if ($node !== []) {
        file_put_contents($dir.'/package.json', json_encode($node));
    }

    return $dir;
}

it('reads the front end, Sanctum and the database from the app', function (array $composer, array $node, string $frontEnd, bool $sanctum) {
    $profile = AppProfile::detect(appWith($composer, $node), 'pgsql');

    expect($profile->frontEnd)->toBe($frontEnd)
        ->and($profile->sanctum)->toBe($sanctum)
        ->and($profile->databaseName())->toBe('PostgreSQL');
})->with([
    'Inertia React' => [['require' => ['inertiajs/inertia-laravel' => '^2.0', 'laravel/sanctum' => '^4.0']], ['devDependencies' => ['@inertiajs/react' => '^2.0']], 'Inertia with React', true],
    'Inertia Vue' => [['require' => ['inertiajs/inertia-laravel' => '^2.0']], ['dependencies' => ['@inertiajs/vue3' => '^2.0']], 'Inertia with Vue', false],
    'Inertia Svelte' => [['require' => ['inertiajs/inertia-laravel' => '^2.0']], ['dependencies' => ['@inertiajs/svelte' => '^2.0']], 'Inertia with Svelte', false],
    'Livewire' => [['require' => ['livewire/livewire' => '^4.0']], [], 'Livewire', false],
    'Blade' => [['require' => ['laravel/framework' => '^13.0']], [], 'Blade', false],
]);

it('falls back to Blade when the app has no readable composer.json', function () {
    $profile = AppProfile::detect(sys_get_temp_dir().'/drd-missing-'.bin2hex(random_bytes(4)), 'sqlsrv');

    expect($profile->frontEnd)->toBe('Blade')
        ->and($profile->sanctum)->toBeFalse()
        ->and($profile->databaseName())->toBe('SQL Server');
});
