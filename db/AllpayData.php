<?php

/**
 * Class AllpayData
 */
class AllpayData extends DAO
{
    function __construct()
    {
        $this->master = $this->database('master', true);
        $this->slave = $this->database('slave', true);
        $this->redis_master = $this->redis('master');
        $this->redis_master = $this->redis('slave');
    }

}


