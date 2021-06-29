<?php

namespace BangerGames\ServerCreator\Commands;


use BangerGames\ServerCreator\Panel\Panel;
use BangerGames\SteamGameServerLoginToken\TokenService;
use HCGCloud\Pterodactyl\Resources\Allocation;
use Illuminate\Console\Command;

class ServerCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bangergames:server-create
    {--serverCount=1 : Server count}
    {--nodeId=2 : Node id}
    {--skipScripts=true : Skip scripts}
    ';

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
//        $service = new TokenService();
//        $accountList = $service->getAccountList();
//        $servers = $accountList->response->servers ?? [];
//        foreach ($servers as $server) {
//            var_dump($service->deleteAccount($server->steamid));
//        }
        $panel = new Panel();
        $serverCount = $this->option('serverCount');
        $nodeId = $this->option('nodeId');
        $skipScripts = filter_var($this->option('skipScripts'), FILTER_VALIDATE_BOOLEAN);
        $bar = $this->output->createProgressBar($serverCount);
        $bar->start();
        for ($i=1;$i<=$serverCount;$i++) {
            $newServer = $panel->createServer($nodeId, [
                'skip_scripts' => $skipScripts
            ]);
            $bar->advance();
        }
        $bar->finish();
    }
}
