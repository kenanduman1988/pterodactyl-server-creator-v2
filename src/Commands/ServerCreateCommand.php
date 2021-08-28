<?php

namespace BangerGames\ServerCreator\Commands;


use BangerGames\ServerCreator\Exceptions\AllocationNotFoundException;
use BangerGames\ServerCreator\Exceptions\NodeNotFoundException;
use BangerGames\ServerCreator\Panel\Panel;
use Illuminate\Console\Command;


class ServerCreateCommand extends Command
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
    {--mountAll=true : Auto mount}

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
        $mountAll = filter_var($this->option('mountAll'), FILTER_VALIDATE_BOOLEAN);
        $bar = $this->output->createProgressBar($serverCount);
        $bar->start();
        for ($i=1;$i<=$serverCount;$i++) {
            try{
                $newServer = $panel->createServer($nodeId, [
                    'skip_scripts' => $skipScripts,
                    'mount_all' => $mountAll,
                ]);
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
