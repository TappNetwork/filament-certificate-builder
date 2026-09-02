<?php

namespace Tapp\FilamentCertificateBuilder\Commands;

use Illuminate\Console\Command;

class FilamentCertificateBuilderCommand extends Command
{
    public $signature = 'filament-certificate-builder';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
