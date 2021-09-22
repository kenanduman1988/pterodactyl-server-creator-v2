<?php

namespace BangerGames\ServerCreator\Commands;


use BangerGames\ServerCreator\Exceptions\AllocationNotFoundException;
use BangerGames\ServerCreator\Exceptions\NodeNotFoundException;
use BangerGames\ServerCreator\Panel\Panel;
use Illuminate\Console\Command;


class PanelSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bangergames:panel-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync command for pterodactyl';



    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $panel = new Panel();

        $panel->syncLocations();
        $this->line('Locations are synced');

        $panel->syncNodes();
        $this->line('Nodes are synced');

        $panel->syncServers();
        $this->line('Servers are synced');

        $panel->deleteNotExistsServers();
        $this->line('Not exists servers are deleted');

        $panel->syncSteamTokens();
        $this->line('Steam ids are synced');


        $this->line('Done');
    }
}
