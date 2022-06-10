<?php

require 'vendor/autoload.php';

$client = new GuzzleHttp\Client(['base_uri' => "http://httpbin.org"]);

$client->request('GET', '/foo', ['force_ip_resolve' => 'v4']);
