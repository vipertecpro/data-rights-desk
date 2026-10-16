<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;
use Vipertecpro\DataRightsDesk\Exceptions\NoticeIncomplete;
use Vipertecpro\DataRightsDesk\Notices\NoticeRepository;

class NoticeCommand extends Command
{
    protected $signature = 'data-rights:notice {--pretend : Build and compare without publishing}';

    protected $description = 'Build the notice in every offered language and publish a new version when it changed';

    public function handle(NoticeRepository $notices): int
    {
        $problems = $notices->problems();

        if ($problems !== []) {
            $this->error('The notice cannot be published. Fix these first:');

            foreach ($problems as $problem) {
                $this->line('  - '.$problem);
            }

            return self::FAILURE;
        }

        $version = $notices->currentVersion();
        $rows = [];

        foreach ($notices->buildAll() as $locale => $built) {
            $current = $version === null ? null : $notices->find($version, $locale);
            $rows[] = [$locale, substr($built->hash, 0, 12), $current === null ? 'new' : ($current->hash === $built->hash ? 'unchanged' : 'changed')];
        }

        $this->table(['Locale', 'Hash', 'Since the published version'], $rows);

        if ($this->option('pretend')) {
            $this->info($notices->hasChanges() ? 'Pretend: a new version would be published.' : 'Pretend: nothing changed.');

            return self::SUCCESS;
        }

        try {
            $published = $notices->publish();
        } catch (NoticeIncomplete $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($published === null) {
            $this->info("Nothing changed. Version {$version} stays current.");

            return self::SUCCESS;
        }

        $this->info("Published notice version {$published}.");

        return self::SUCCESS;
    }
}
