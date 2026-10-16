<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeSourceCommand extends Command
{
    protected $signature = 'data-rights:make-source {name : Class name, for example Orders}';

    protected $description = 'Create an Exporter and Eraser class in app/DataRights';

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

        $stub = File::get(__DIR__.'/../../stubs/source.stub');

        $namespace = rtrim($this->laravel->getNamespace(), '\\').'\\DataRights';

        File::ensureDirectoryExists(dirname($path));
        File::put($path, str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ name }}'],
            [$namespace, $class, Str::headline($class)],
            $stub,
        ));

        $this->info("Created {$path}.");
        $this->line("Add {$namespace}\\{$class}::class to the 'sources' list in config/data-rights.php.");

        return self::SUCCESS;
    }
}
