<?php declare(strict_types=1);

use Masterminds\HTML5;

function myip() : string {
    $ipElement = new DOMXPath((new HTML5())->loadHTMLFile('https://www.whatismyip.org/'));
    $response = $ipElement->query('//*[contains(text(), "Your IP:")]');
    if ($response->count() === 0) {
        exit('can not determine ip');
    }
    $ipText = $response->item(0)->parentNode->textContent;
    if (preg_match('/((\d+\.){3}(\d+))/', $ipText, $matches) === false) {
        exit('can not determine ip');
    }
    return $matches[1];
}

require 'vendor/autoload.php';
(require 'config.php')(function(string $directadmin_url, string $username, string $password, array $hosts) {
    $daDNSControlAPI = function(string $domain, $url) use ($directadmin_url, $username, $password) : string {
        static $cache = [];
        if (array_key_exists($domain, $cache) === false) {
            $cache[$domain] = [];
        } elseif (array_key_exists($url, $cache[$domain])) {
            return $cache[$domain][$url];
        }
        return $cache[$domain][$url] = file_get_contents($directadmin_url . "/CMD_API_DNS_CONTROL?domain=" . $domain . $url, false, stream_context_create([
            "http" => [
                "header" => "Authorization: Basic " . base64_encode($username . ':' . $password)
            ]
        ]));
    };

    $createDomainDNSAPI = function(string $domain) use ($daDNSControlAPI) : callable {
        $cmdApiDNSChange = function(string $url) use ($daDNSControlAPI, $domain) : bool {
            return stripos($daDNSControlAPI($domain, $url), 'error=0') !== false;
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
        $domainDNSAPI = $createDomainDNSAPI($domain);
        foreach ($dnsHosts as $host) {
            if (preg_match('/^' . preg_quote($host, '/') . '.*' . preg_quote($myip, '/') . '$/m', $daDNSControlAPI($domain, '')) === 1) {
                echo PHP_EOL . 'U2D: ' . $host;
            } elseif ($domainDNSAPI($host, $myip) === false) {
                echo PHP_EOL . 'ERR: ' . $host;
            }
        }
        print(PHP_EOL . 'updated');
    }
    exit(PHP_EOL . 'done' . PHP_EOL);
});