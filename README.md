DirectAdmin DynDNS control
--

config.php:
`<?php return function(callable $app) {
     $app(DA_URL, DA_USER, DA_PASS, [
        DOMAIN => [HOST, HOST, HOST]
     ]);
 };`
 
 Replacements
 ---
 - DA_URL : https://web.host.tld
 - DA_USER/DA_PASS: credentials for logging into DirectAdmin
 - DOMAIN: mysite.tld
 - HOST: subdomains, e.g. www
 
 Steps
 ---
 - create config
 - install composer
 - install php mbstring
 - \# composer install
 - \# php cron.php
 - add to crontab
