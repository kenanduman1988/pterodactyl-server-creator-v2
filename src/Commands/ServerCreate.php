<?php

namespace BangerGames\ServerCreator\Commands;


use BangerGames\ServerCreator\Exceptions\AllocationNotFoundException;
use BangerGames\ServerCreator\Exceptions\NodeNotFoundException;
use BangerGames\ServerCreator\Panel\Panel;
use Pterodactyl\Models\MountServer;
use Pterodactyl\Repositories\Eloquent\MountRepository;
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
        $panel = new Panel();
        $serverCount = $this->option('serverCount');
        $nodeId = $this->option('nodeId');
        $skipScripts = filter_var($this->option('skipScripts'), FILTER_VALIDATE_BOOLEAN);
        $bar = $this->output->createProgressBar($serverCount);
        $bar->start();
        for ($i=1;$i<=$serverCount;$i++) {
            try{
                $newServer = $panel->createServer($nodeId, [
                    'skip_scripts' => $skipScripts
                ]);
                // I'm not sure if we should this before or after we copy the csgo files into docker directory
                $mountList = MountRepository::getMountListForServer($newServer);
                foreach ($mountList as $mount)
                {
                    $mountServer = (new MountServer())->forceFill([
                        'mount_id' => $mount->id,
                        'server_id' => $newServer->id,
                    ]);
                    $mountServer->saveOrFail();
                }

                $this->line(sprintf('Server %s was created', $newServer->name));
            } catch (AllocationNotFoundException $e) {
                $this->error($e->getMessage());
                $bar->finish();
                break;
            } catch (NodeNotFoundException $e) {
                $this->error($e->getMessage());
                $bar->finish();
                break;
            }

            $bar->advance();
        }
        $bar->finish();
        $this->line('Done');
    }
}
