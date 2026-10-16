<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Exceptions\UnknownPurpose;
use Vipertecpro\DataRightsDesk\Support\Subjects;

/** s.6(10): the proof of notice and consent for one person, as JSON. */
class ProofCommand extends Command
{
    protected $signature = 'data-rights:proof {email : The person\'s email} {--purpose= : One purpose only} {--output= : Write to this file instead of the screen}';

    protected $description = 'Write the consent proof for one person as JSON';

    public function handle(DataRightsManager $dataRights): int
    {
        $subject = Subjects::findByEmail((string) $this->argument('email'));

        if ($subject === null) {
            $this->error('No person with that email.');

            return self::FAILURE;
        }

        $purpose = $this->option('purpose');

        try {
            $proof = $dataRights->proof($subject, is_string($purpose) && $purpose !== '' ? $purpose : null);
        } catch (UnknownPurpose $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $json = (string) json_encode($proof, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $output = $this->option('output');

        if (is_string($output) && $output !== '') {
            File::put($output, $json."\n");
            $this->info("Proof written to {$output}.");

            return self::SUCCESS;
        }

        $this->line($json);

        return self::SUCCESS;
    }
}
