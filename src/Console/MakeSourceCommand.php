<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Vipertecpro\DataRightsDesk\Purposes\PurposeRegistry;

class MakeSourceCommand extends Command
{
    protected $signature = 'data-rights:make-source
        {name : Class name, for example Orders}
        {--purpose=* : Make a PurposeEraser for these purposes instead, for example --purpose=marketing}';

    protected $description = 'Create an Exporter and Eraser class (or, with --purpose, a PurposeEraser) in app/DataRights';

    public function handle(): int
    {
        $class = Str::studly((string) $this->argument('name'));

        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $class) !== 1) {
            $this->error('Use a class name with letters and digits only, for example Orders.');

            return self::FAILURE;
        }

        $path = app_path('DataRights/'.$class.'.php');

        if (File::exists($path)) {
            $this->error("{$path} already exists.");

            return self::FAILURE;
        }

        $purposes = array_values(array_filter(array_map('strval', (array) $this->option('purpose'))));

        foreach ($purposes as $purpose) {
            if (! $this->laravel->make(PurposeRegistry::class)->has($purpose)) {
                $this->error("Purpose [{$purpose}] is not in config/data-rights.php.");

                return self::FAILURE;
            }
        }

        $stub = File::get(__DIR__.'/../../stubs/'.($purposes === [] ? 'source.stub' : 'purpose-source.stub'));

        $namespace = rtrim($this->laravel->getNamespace(), '\\').'\\DataRights';

        File::ensureDirectoryExists(dirname($path));
        File::put($path, str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ name }}', '{{ purposes }}'],
            [$namespace, $class, Str::headline($class), implode(', ', array_map(fn (string $p): string => var_export($p, true), $purposes))],
            $stub,
        ));

        $this->info("Created {$path}.");
        $this->line("Add {$namespace}\\{$class}::class to the 'sources' list in config/data-rights.php.");

        return self::SUCCESS;
    }
}
