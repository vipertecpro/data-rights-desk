<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;
use Vipertecpro\DataRightsDesk\Retention\RetentionKeeper;

class RetainCommand extends Command
{
    protected $signature = 'data-rights:retain {--pretend : Count without changing anything}';

    protected $description = 'Erase records past their retention period (legal holds are kept)';

    public function handle(RetentionKeeper $keeper): int
    {
        $report = $keeper->run((bool) $this->option('pretend'));

        $rows = [];

        foreach ($report->counts as $key => $count) {
            $rows[] = [str_replace('_', ' ', $key), $count];
        }

        foreach ($report->models as $class => $count) {
            $rows[] = ['  '.$class, $count];
        }

        $this->table(['Step', $report->pretend ? 'Would erase or warn' : 'Erased or warned'], $rows);
        $this->line("Records are kept at least {$report->keepDays} days (Rule 8(3)).");

        if ($report->pretend) {
            $this->info('Pretend: nothing was changed.');
        }

        return self::SUCCESS;
    }
}
