<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;
use Vipertecpro\DataRightsDesk\DataRightsManager;

class CheckCommand extends Command
{
    protected $signature = 'data-rights:check {--json : Print the report as JSON}';

    protected $description = 'Look for gaps in the Data Rights Desk setup (exit code 1 on errors)';

    public function handle(DataRightsManager $dataRights): int
    {
        $report = $dataRights->check();

        if ($this->option('json')) {
            $this->line((string) json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $report->passed() ? self::SUCCESS : self::FAILURE;
        }

        foreach ($report->errors() as $error) {
            $this->line("<fg=red>error</>    {$error['message']}");
        }

        foreach ($report->warnings() as $warning) {
            $this->line("<fg=yellow>warning</>  {$warning['message']}");
        }

        $this->newLine();
        $this->line(count($report->errors()).' error(s), '.count($report->warnings()).' warning(s).');
        $this->line('The checker looks at what this package can see. It is not legal advice.');

        return $report->passed() ? self::SUCCESS : self::FAILURE;
    }
}
