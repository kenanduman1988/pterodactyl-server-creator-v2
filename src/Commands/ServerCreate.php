<?php

namespace BangerGames\PteroDactylServerCreator\Commands;


use Illuminate\Console\Command;

class PterodactylServerCreatorProvider extends Command
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
    protected $description = 'Create command';



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
