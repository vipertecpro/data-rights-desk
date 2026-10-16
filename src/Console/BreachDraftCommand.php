<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;
use Vipertecpro\DataRightsDesk\Breaches\BreachDesk;
use Vipertecpro\DataRightsDesk\Enums\BreachDraftKind;
use Vipertecpro\DataRightsDesk\Models\Breach;

/** Rule 7: prints a draft. The business checks it and sends it. */
class BreachDraftCommand extends Command
{
    protected $signature = 'data-rights:breach-draft {breach : The breach id} {kind=report : alert, report or person} {--lang=en : Language of the person draft}';

    protected $description = 'Print the Board alert, the Board report or the message to affected people';

    public function handle(BreachDesk $desk): int
    {
        $breach = Breach::query()->find((string) $this->argument('breach'));

        if (! $breach instanceof Breach) {
            $this->error('No breach with that id.');

            return self::FAILURE;
        }

        $kind = BreachDraftKind::tryFrom((string) $this->argument('kind'));

        if ($kind === null) {
            $this->error('Kind must be alert, report or person.');

            return self::FAILURE;
        }

        $this->line($desk->draft($breach, $kind, (string) $this->option('lang')));

        return self::SUCCESS;
    }
}
