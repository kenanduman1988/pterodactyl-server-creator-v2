<?php

namespace BangerGames\ServerCreator\Observers;

use App\Jobs\DisableUserRewardJob;
use App\Jobs\PanelServerDeletingJob;
use App\Jobs\PanelServerPowerJob;
use App\Model\RewardBonus;
use App\Model\UserReward;
use BangerGames\ServerCreator\Models\PanelServer;
use BangerGames\ServerCreator\Panel\Panel;
use Carbon\Carbon;

class PanelServerObserver
{
    public function deleting(PanelServer $panelServer)
    {
        $job = new PanelServerDeletingJob($panelServer->server_id, $panelServer->steam_id);
        dispatch($job->onQueue('jobs'));

    }

    public function created(PanelServer $panelServer)
    {
        $job = new PanelServerPowerJob($panelServer->id, 'restart');
        dispatch($job->delay(Carbon::now()->addMinutes(1))->onQueue('jobs'));
    }
}
