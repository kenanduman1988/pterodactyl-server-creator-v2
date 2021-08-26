<?php

namespace BangerGames\ServerCreator\Observers;

use App\Jobs\DisableUserRewardJob;
use App\Model\RewardBonus;
use App\Model\UserReward;
use BangerGames\ServerCreator\Models\PanelServer;
use BangerGames\ServerCreator\Panel\Panel;
use Carbon\Carbon;

class PanelServerObserver
{
    public function deleted(PanelServer $panelServer)
    {
        $panel = new Panel();
        $panel->deleteServer($panelServer);
    }
}
