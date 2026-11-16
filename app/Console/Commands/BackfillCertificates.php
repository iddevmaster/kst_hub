<?php

namespace App\Console\Commands;

use App\Services\CertificateService;
use Illuminate\Console\Command;

class BackfillCertificates extends Command
{
    protected $signature = 'certificates:backfill {--agn= : only this agency id (default: all agencies)}';

    protected $description = 'Issue certificates for past passing tests in courses that have an active template';

    public function handle(CertificateService $svc): int
    {
        $this->info('Backfilling certificates...');
        $res = $svc->backfill($this->option('agn') ?: null);
        $this->info("Issued {$res['issued']} certificate(s), skipped {$res['skipped']} test(s).");
        return self::SUCCESS;
    }
}
