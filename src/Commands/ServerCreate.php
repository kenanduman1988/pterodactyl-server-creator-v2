<?php

namespace BangerGames\ServerCreator\Commands;


use Illuminate\Console\Command;

class ServerCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server-creator:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create command for pterodactyl';



    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        var_dump('test');
    }
}
