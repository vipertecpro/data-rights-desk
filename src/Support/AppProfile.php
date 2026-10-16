<?php

namespace Vipertecpro\DataRightsDesk\Support;

/**
 * What the install command can tell about the app from its composer.json, package.json and
 * database config, so it can print next steps that fit. It only reads; it never edits app code.
 */
final readonly class AppProfile
{
    public function __construct(
        public string $database,
        public string $frontEnd,
        public bool $sanctum,
    ) {}

    public static function detect(string $basePath, string $databaseDriver): self
    {
        $composer = self::json($basePath.'/composer.json');
        $node = self::json($basePath.'/package.json');

        $php = array_merge((array) ($composer['require'] ?? []), (array) ($composer['require-dev'] ?? []));
        $js = array_merge((array) ($node['dependencies'] ?? []), (array) ($node['devDependencies'] ?? []));

        return new self($databaseDriver, self::frontEnd($php, $js), array_key_exists('laravel/sanctum', $php));
    }

    public function databaseName(): string
    {
        return match ($this->database) {
            'sqlite' => 'SQLite',
            'mysql' => 'MySQL',
            'mariadb' => 'MariaDB',
            'pgsql' => 'PostgreSQL',
            'sqlsrv' => 'SQL Server',
            default => $this->database,
        };
    }

    public function isInertia(): bool
    {
        return str_starts_with($this->frontEnd, 'Inertia');
    }

    /**
     * @param  array<string, mixed>  $php
     * @param  array<string, mixed>  $js
     */
    private static function frontEnd(array $php, array $js): string
    {
        if (array_key_exists('inertiajs/inertia-laravel', $php)) {
            foreach (['@inertiajs/react' => 'React', '@inertiajs/vue3' => 'Vue', '@inertiajs/svelte' => 'Svelte'] as $package => $name) {
                if (array_key_exists($package, $js)) {
                    return 'Inertia with '.$name;
                }
            }

            return 'Inertia';
        }

        if (array_key_exists('livewire/livewire', $php)) {
            return 'Livewire';
        }

        return 'Blade';
    }

    /** @return array<string, mixed> */
    private static function json(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
