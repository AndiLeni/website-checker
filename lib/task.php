<?php

class Task
{


    public function __construct()
    {
        $this->client = new GuzzleHttp\Client();
    }
}
