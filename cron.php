<?php declare(strict_types=1);

use Masterminds\HTML5;

require 'vendor/autoload.php';
require 'config.php';

$apiCall = function($url) use ($directadmin_url, $domain, $username, $password) {
    $status = file_get_contents($directadmin_url . "/CMD_API_DNS_CONTROL?domain=" . $domain . $url, false, stream_context_create([
        "http" => [
            "header" => "Authorization: Basic " . base64_encode($username . ':' . $password)
        ]
    ]));
    return stripos($status, 'error=0') !== false;
};

$ipElement = new DOMXPath((new HTML5())->loadHTMLFile('https://www.whatismyip.org/'));
$ipText = $ipElement->query('//*[contains(text(), "Your IP:")]')->item(0)->parentNode->textContent;
if (preg_match('/Your IP: ((\d+\.){3}(\d+))/', $ipText, $matches) === false) {
    exit('can not determine ip');
}
$myip = $matches[1];

$current_dns = file_get_contents($directadmin_url . "/CMD_API_DNS_CONTROL?domain=" . $domain, false, stream_context_create([
    "http" => [
        "header" => "Authorization: Basic " . base64_encode($username . ':' . $password)
    ]
]));

foreach ($hosts as $host) {
    if (preg_match('/^' . preg_quote($host, '/') . '.*' . preg_quote($myip, '/') . '$/m', $current_dns) === 1) {
        echo PHP_EOL . 'U2D: ' . $host;
        continue;
    }

    if ($apiCall("&action=select&arecs0=name=" . $host . "&value=" . gethostbyname($host))  === false) {
        echo PHP_EOL . 'ERR: ' . $host;
        continue;
    }


    if ($apiCall("&action=add&type=A&name=" . $host . "&value=" . $myip)  === false) {
        echo PHP_EOL . 'ERR: ' . $host;
        continue;
    }
}

exit(PHP_EOL . 'updated' . PHP_EOL);