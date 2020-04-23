<?php

require 'config.php';

$apiCall = function($url) use ($directadmin_url, $domain, $username, $password) {
    $status = file_get_contents($directadmin_url . "/CMD_API_DNS_CONTROL?domain=" . $domain . $url, false, stream_context_create([
        "http" => [
            "header" => "Authorization: Basic " . base64_encode($username . ':' . $password)
        ]
    ]));
    return stristr($status, 'error=0') !== false;
};

$myip = file_get_contents($reflector_url . '/reflector.php', false, stream_context_create(array(
    "ssl"=>array(
        "allow_self_signed"=>true,
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    )
)));
$current_dns = file_get_contents($directadmin_url . "/CMD_API_DNS_CONTROL?domain=" . $domain, false, stream_context_create([
    "http" => [
        "header" => "Authorization: Basic " . base64_encode($username . ':' . $password)
    ]
]));

if (preg_match('/^' . preg_quote($host, '/') . '.*' . preg_quote($myip, '/') . '$/m', $current_dns) === 1) {
    exit('up2date');
}

if ($apiCall("&action=select&arecs0=name=" . $host . "&value=" . gethostbyname($host))  === false) {
    exit('error');
}


if ($apiCall("&action=add&type=A&name=" . $host . "&value=" . $myip)  === false) {
    exit('error');
}

exit('updated');