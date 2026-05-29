<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Process;

class Parser extends Page
{
    protected string $view = 'filament.pages.parser';

    public $output = '';


    public function runParser(){

        $result = Process::run('"C:\Program Files\nodejs\node.exe" ' . base_path('parser.js'));

        // нужно будет изменить путь

        //$result = Process::run('node ' . base_path('parser.js'));
        //$result = Process::run('/usr/bin/node ' . base_path('parser.js'));
        // узнается на сервере командой: which node

        $this->output = $result->output();




    }




}


