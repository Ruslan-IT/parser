<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Process;

class Parser extends Page
{
    protected string $view = 'filament.pages.parser';

    public $output = '';


    public function runParser(){

        //$result = Process::run('"C:\Program Files\nodejs\node.exe" ' . base_path('parser.js'));

        // нужно будет изменить путь

        //$result = Process::run('node ' . base_path('parser.js'));



        $result = Process::path(base_path())
            ->run('/usr/bin/node parser.js');

        // узнается на сервере командой: which node

        //$this->output = $result->output();

        $this->output = $result->errorOutput();




    }




}


