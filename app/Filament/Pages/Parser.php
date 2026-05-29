<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Process;

class Parser extends Page
{
    protected string $view = 'filament.pages.parser';

    public $output = '';


    public function runParser(){

        $result = Process::path(base_path())
            ->env([
                'PLAYWRIGHT_BROWSERS_PATH' => '0',
            ])
            ->run('/usr/bin/node parser.js');

        $this->output =
            $result->output() . "\n\n" .
            $result->errorOutput();




    }




}


