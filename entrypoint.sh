#!/bin/sh

WORKDIR=/usr/src/ec-cube
export WORKDIR

if [ ! -e "${WORKDIR}/app/config/eccube/config.yml" ]; then
    php <<'HERE' || exit 
<?php
$stderr = fopen('php://stderr', 'w');
$timeout = 30;
$s = false;
while (--$timeout > 0) {
    $s = @fsockopen($_ENV["DBSERVER"] ?: "localhost", $_ENV["DBPORT"] ?: 5432, $err, $errstr, 1);
    if ($s !== false) {
        break;
    }
    sleep(1);
}
if ($s === false) {
    fwrite($stderr, "timeout while waiting for database serve to start up\n");
    fflush($stderr);
    exit(1);
}
fclose($s);

$workdir = $_ENV["WORKDIR"];
$entry = posix_getpwnam("www-data");
$env = array_merge($_ENV, ["HOME" => $workdir]);
posix_setgid($entry["gid"]);
posix_setuid($entry["uid"]);
function run($args) {
    global $env;
    if (!pcntl_fork()) {
        pcntl_exec(PHP_BINARY, $args, $env);
        exit(255);
    }
    $status = 0;
    pcntl_wait($status);
    if ($status) {
        exit($status);
    }
}

run([$workdir . "/eccube_install.php", "pgsql", "none"]);
run(["app/console", "plugin:develop", "install", "--code", "PayJp"]);
run(["app/console", "plugin:develop", "enable", "--code", "PayJp"]);
HERE
fi

exec /usr/local/bin/docker-php-entrypoint $@
