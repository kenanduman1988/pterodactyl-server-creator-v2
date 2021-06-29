<?php

namespace BangerGames\ServerCreator\Exceptions;

use Exception;

class AllocationNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Allocation on node not found.');
    }
}