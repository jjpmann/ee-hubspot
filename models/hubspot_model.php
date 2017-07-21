<?php

class Hubspot_model extends CI_Model
{

    protected $driver;

    public function __construct()
    {
        // try {
        //     $this->driver = new Subscribe\Drivers\RealMagnetDriver();
        // } catch (\RealMagnet\RealMagnetException $e) {
        //     $this->driver = new Subscribe\Drivers\NullDriver();
        // }
    }

}
