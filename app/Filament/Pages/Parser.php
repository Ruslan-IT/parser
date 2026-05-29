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
            ->run('/usr/bin/node -v');

        $this->output = $result->output();




    }




}


