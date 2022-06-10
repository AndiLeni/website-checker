<?php


require 'vendor/autoload.php';

use GuzzleHttp\TransferStats;


// $url = readline("Enter the URL: ");
$url = "google.com";


function padding($left, $right)
{
    $left = str_pad($left, 30, " ", STR_PAD_RIGHT);
    $right = str_pad($right, 30, " ", STR_PAD_LEFT);
    return $left . $right . "<br>";
}


function request_option($accept_encoding, $http_version, $ip_version)
{
    return [
        'decode_content' => $accept_encoding,
        'allow_redirects' => [
            'max'             => 5,
            'referer'         => true,
            'protocols'       => ["http", "https"],
            'track_redirects' => true
        ],
        'verify' => true,
        'timeout' => 3,
        "version" => $http_version,
        "force_ip_resolve" => $ip_version,
        'on_stats' => function (\GuzzleHttp\TransferStats $stats) {
            $handlerStats = $stats->getHandlerStats();

            if ($stats->hasResponse()) {
                echo '<div class="box">';

                echo $stats->getResponse()->getStatusCode();
                echo padding("Transfer time:", $stats->getTransferTime());
                echo padding("Effective URL:", $stats->getEffectiveUri());
                echo padding("Redirects:", $handlerStats["redirect_count"]);
                echo padding("Content length:", $handlerStats["download_content_length"]);
                echo padding("size_download:", $handlerStats["size_download"]);
                dump($stats->getResponse());

                echo "</div>";
            } else {
                dump($stats->getHandlerErrorData());
            }
        }
    ];
}


$http_protocols = ['http', 'https'];
$http_versions = [1.0, 1.1, 2];
$ip_versions = ['v4', 'v6'];
$accept_encodings = ['gzip', 'deflate', "br"];

echo <<<EOT
<style>
body {
    font-family: monospace;
    font-size: 14px;
}
.box {
    border: 2px solid #000;
    padding: 10px;
    margin: 10px;
}
</style>
EOT;

echo "<h1>Checking {$url}</h1>";
echo "Performing " . count($http_protocols) * count($http_versions) * count($ip_versions) * count($accept_encodings) . " requests...<br>";

$i = 0;
foreach ($http_protocols as $http_protocol) {
    foreach ($http_versions as $http_version) {
        foreach ($ip_versions as $ip_version) {
            foreach ($accept_encodings as $accept_encoding) {
                $i++;

                $base_url = "{$http_protocol}://{$url}";

                echo "<h2>Request: {$i}</h2>";
                echo '<div class="box">';
                echo "<h3>Procotol: {$http_protocol} | http-version: {$http_version} | IP-version: {$ip_version} | accepted-encoding: {$accept_encoding} </h3>";

                $client = new GuzzleHttp\Client(['base_uri' => $base_url]);

                try {
                    $response = $client->request(
                        'GET',
                        '/',
                        request_option($accept_encoding, $http_version, $ip_version)
                    );

                    // $redirects = $response->getHeader('X-Guzzle-Redirect-History');
                    // echo padding("Redirect-History:", implode(' -> ', $redirects));
                    // // echo "Redirect-History : " . implode(' -> ', $redirects);

                    // $redirect_history = $response->getHeader('X-Guzzle-Redirect-Status-History');
                    // echo "Redirect-Status-History : " . implode(' -> ', $redirect_history) . "\n<br>";
                } catch (Exception $e) {
                    dump($e->getMessage());
                } finally {
                    echo "</div>";
                }
            }
        }
    }
}
