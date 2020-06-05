DirectAdmin DynDNS control
--

config.php:
`<?php return function(callable $app) {
     $app(DA_URL, DA_USER, DA_PASS, [
        DOMAIN => [HOST, HOST, HOST]
     ]);
 };`