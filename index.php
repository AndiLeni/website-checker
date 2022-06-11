<?php


require 'vendor/autoload.php';

use GuzzleHttp\TransferStats;


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
.tag {
    background-color: #4287f5;
    color: #fff;
    border-radius: 3px;
    padding-top: 4px;
    padding-bottom: 4px;
    padding-left: 7px;
    padding-right: 7px;
}
</style>
EOT;

$url = $_POST['url'] ?? '';
if (empty($url)) {
    echo <<<EOT
    <h1>Enter URL:</h1>
    <form method="post">
        <input type="text" name="url" placeholder="URL (without http/https)">
        <input type="submit" value="Submit">
    </form>
    EOT;
    exit;
}



function padding($left, $right)
{
    $left = str_pad($left, 30, " ", STR_PAD_RIGHT);
    $right = str_pad($right, 30, " ", STR_PAD_LEFT);
    return $left . $right . "<br>";
}

function print_html_line($text)
{
    echo $text . "<br>";
}

function get_status_color(int $status)
{
    if (in_array($status, [200, 201, 202, 203, 204, 205, 206])) {
        return "green";
    } else if (in_array($status, [300, 301, 302, 303, 304, 305, 306])) {
        return "blue";
    } else if (in_array($status, [400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 419, 420, 421, 422, 423, 424, 425, 426, 428, 429, 431, 444, 449, 450, 451, 494, 495, 496, 497, 498, 499])) {
        return "red";
    } else {
        return "black";
    }
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
        "debug" => false,
        'on_stats' => function (\GuzzleHttp\TransferStats $stats) {
            global $redirects;
            global $errors;
            global $success;
            $handlerStats = $stats->getHandlerStats();

            if ($stats->hasResponse()) {
                $response = $stats->getResponse();

                if (in_array($response->getStatusCode(), [200, 201, 202, 203, 204, 205, 206])) {
                    $success++;
                }
                if (in_array($response->getStatusCode(), [300, 301, 302, 303, 304, 305, 306])) {
                    $redirects++;
                }
                if (in_array($response->getStatusCode(), [400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 419, 420, 421, 422, 423, 424, 425, 426, 428, 429, 431, 444, 449, 450, 451, 494, 495, 496, 497, 498, 499])) {
                    $errors++;
                }

                $status_color = get_status_color($response->getStatusCode());
                echo '<div class="box" style="border: 3px solid ' . $status_color . '">';

                print_html_line($response->getReasonPhrase() . " - " . $response->getStatusCode());
                print_html_line("Transfer time: " . $stats->getTransferTime());
                print_html_line("Effective URL: " . $stats->getEffectiveUri());
                print_html_line("Content length: " . $handlerStats["download_content_length"] . " bytes");
                print_html_line("Download size: " . $handlerStats["size_download"] . " bytes");
                print_html_line("content_type: " . $handlerStats["content_type"]);
                print_html_line("redirect_url: " . $handlerStats["redirect_url"]);
                print_html_line("primary_ip: " . $handlerStats["primary_ip"]);
                print_html_line("primary_port: " . $handlerStats["primary_port"]);

                echo "</div>";
            } else {
                strval($stats->getHandlerErrorData());
            }
        }
    ];
}


$http_protocols = ['http', 'https'];
$http_versions = [1.0, 1.1, 2];
$ip_versions = ['v4', 'v6'];
$accept_encodings = ['gzip', 'deflate'];



echo "<h1>Checking {$url}</h1>";
echo "Performing " . count($http_protocols) * count($http_versions) * count($ip_versions) * count($accept_encodings) . " requests...<br>";

ob_start();

$request_num = 0;
$redirects = 0;
$errors = 0;
$success = 0;

foreach ($http_protocols as $http_protocol) {
    foreach ($http_versions as $http_version) {
        foreach ($ip_versions as $ip_version) {
            foreach ($accept_encodings as $accept_encoding) {
                $request_num++;

                $base_url = "{$http_protocol}://{$url}";

                echo "<h2>Request: {$request_num}</h2>";
                echo '<div class="box">';
                echo <<<EOT
                    <h3>Procotol: 
                    <span class='tag'>{$http_protocol}</span> 
                    | 
                    http-version: <span class='tag'>{$http_version}</span> 
                    | 
                    IP-version: <span class='tag'>{$ip_version}</span> 
                    | 
                    accepted-encoding: <span class='tag'>{$accept_encoding}</span> 
                    </h3>
                    EOT;

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
                    echo $e->getMessage();
                } finally {
                    echo "</div>";
                }
            }
        }
    }
}

$html_details = ob_get_clean();

echo "<h1>Summary</h1>";
echo "Success: {$success}<br>";
echo "Redirects: {$redirects}<br>";
echo "Errors: {$errors}<br>";

echo "<h1>Details</h1>";
echo $html_details;
