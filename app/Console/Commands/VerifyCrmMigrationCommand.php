<?php

namespace App\Console\Commands;

use App\Services\Crm\CrmMigrationVerifier;
use Illuminate\Console\Command;

class VerifyCrmMigrationCommand extends Command
{
    protected $signature = 'crm:verify-migration';

    protected $description = 'Verify CRM redesign data migration integrity';

    public function handle(CrmMigrationVerifier $verifier): int
    {
        $result = $verifier->verify();

        $this->table(
            ['Check', 'Expected', 'Actual', 'OK', 'Note'],
            collect($result['checks'])->map(fn ($c) => [
                $c['name'],
                $c['expected'],
                $c['actual'],
                $c['ok'] ? 'yes' : 'NO',
                $c['note'] ?? '',
            ])->all()
        );

        if ($result['ok']) {
            $this->info('CRM migration verification passed.');

            return self::SUCCESS;
        }

        $this->error('CRM migration verification failed. Review mismatches before cleanup.');

        return self::FAILURE;
    }
}
