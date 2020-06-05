<?php declare(strict_types=1);

use Masterminds\HTML5;

require 'vendor/autoload.php';
require 'config.php';

function myip() : string {
    $ipElement = new DOMXPath((new HTML5())->loadHTMLFile('https://www.whatismyip.org/'));
    $ipText = $ipElement->query('//*[contains(text(), "Your IP:")]')->item(0)->parentNode->textContent;
    if (preg_match('/Your IP: ((\d+\.){3}(\d+))/', $ipText, $matches) === false) {
        exit('can not determine ip');
    }
    return $matches[1];
}

$cmdApiDnsControl = function(string $domain, $url) use ($directadmin_url, $username, $password) : string {
    return file_get_contents($directadmin_url . "/CMD_API_DNS_CONTROL?domain=" . $domain . $url, false, stream_context_create([
        "http" => [
            "header" => "Authorization: Basic " . base64_encode($username . ':' . $password)
        ]
    ]));
};

$api = function(string $domain) use ($cmdApiDnsControl) : callable {
    $cmdApiDNSChange = function(string $url) use ($cmdApiDnsControl, $domain) : bool {
        return stripos($cmdApiDnsControl($domain, $url), 'error=0') !== false;
    };
    return function(string $host, string $myip) use ($cmdApiDNSChange) : bool {
        if ($cmdApiDNSChange("&action=select&arecs0=" . urlencode("name=" . $host . "&value=" . gethostbyname($host)))  === false) {
            return false;
        }
        if ($cmdApiDNSChange("&action=add&type=A&name=" . $host . "&value=" . $myip)  === false) {
            return false;
        }
        return true;
    };
};

$myip = myip();
echo PHP_EOL . 'MYIP ' . $myip;

foreach ($hosts as $domain => $dnsHosts) {
    echo PHP_EOL . 'DOMAIN: ' . $domain;

    $apiCall = $api($domain);

    $current_dns = file_get_contents($directadmin_url . "/CMD_API_DNS_CONTROL?domain=" . $domain, false, stream_context_create([
        "http" => [
            "header" => "Authorization: Basic " . base64_encode($username . ':' . $password)
        ]
    ]));

    foreach ($dnsHosts as $host) {
        if (preg_match('/^' . preg_quote($host, '/') . '.*' . preg_quote($myip, '/') . '$/m', $current_dns) === 1) {
            echo PHP_EOL . 'U2D: ' . $host;
        } elseif ($apiCall($host, $myip) === false) {
            echo PHP_EOL . 'ERR: ' . $host;
        }
    }
    print(PHP_EOL . 'updated');
}

exit(PHP_EOL . 'done' . PHP_EOL);