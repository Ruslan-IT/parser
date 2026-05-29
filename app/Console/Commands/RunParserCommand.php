<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

#[Signature('parser:run')]
#[Description('Command description')]
class RunParserCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Парсер запущен');

        $result = Process::run('node parser.js');

        $this->info($result->output());

        $this->info('Парсер завешен');
    }
}
