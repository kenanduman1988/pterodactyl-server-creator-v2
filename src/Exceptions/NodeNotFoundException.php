<?php

namespace BangerGames\ServerCreator\Exceptions;

use Exception;

class NodeNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Node not found.');
    }
}
