<?php

namespace BangerGames\ServerCreator\Panel\Game;

class CSGO
{
    const ENV_GAME_MODE = 'GAME_MODE';
    const ENV_BUSY_BY_MATCH = 'BUSY_BY_MATCH';
    const ENV_BUSY_BY_APP = 'BUSY_BY_APP';
    /**
     * This mapping need for startup command
     * key: teamSize
     */
    const GAME_MODE = [
        1 => 1,
        2 => 2,
        3 => 1,
        4 => 1,
        5 => 1,
    ];

    /**
     * This mapping need for startup command
     * key: teamSize
     */
    const GAME_TYPE = [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
    ];
}
