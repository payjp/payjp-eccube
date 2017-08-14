#!/bin/sh

PLUGINDIR=/usr/src/ec-cube-plugins
export PLUGINDIR
WORKDIR=/usr/src/ec-cube
export WORKDIR

if [ ! -e "${WORKDIR}/app/config/eccube/config.yml" ]; then
    php <<'HERE' || exit 
<?php
$stderr = fopen('php://stderr', 'w');
$timeout = 60;
$s = false;
$dbtype = $_ENV['DBTYPE'];
$host = $_ENV['DBSERVER'] ?: 'localhost';
$port = $_ENV['DBPORT'] ?: ($dbtype == 'mysql' ? 3306: ($dbtype == 'pgsql' ? 5432: -1));
fwrite($stderr, "waiting for $host:$port to accept connections...\n");
fflush($stderr);
while (--$timeout > 0) {
    $s = @fsockopen($host, $port, $err, $errstr, 1);
    if ($s !== false) {
        break;
    }
    sleep(1);
}
if ($s === false) {
    fwrite($stderr, "timeout while waiting for database server to start up.\n");
    fflush($stderr);
    exit(1);
}
fclose($s);

$workdir = $_ENV['WORKDIR'];
$plugindir = $_ENV['PLUGINDIR'];
$entry = posix_getpwnam('www-data');
$env = array_merge($_ENV, ['HOME' => $workdir]);

function run($args) {
    global $env, $entry;
    if (!pcntl_fork()) {
        posix_setgid($entry['gid']);
        posix_setuid($entry['uid']);
        pcntl_exec(PHP_BINARY, $args, $env);
        exit(255);
    }
    $status = 0;
    pcntl_wait($status);
    if ($status) {
        exit($status);
    }
}

function recursive_copy($src, $dest) {
    global $entry;
    mkdir($dest);
    chown($dest, $entry['uid']);
    chgrp($dest, $entry['gid']);
    foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $src, FilesystemIterator::KEY_AS_PATHNAME
                      | FilesystemIterator::CURRENT_AS_SELF),
            RecursiveIteratorIterator::SELF_FIRST) as $info) {
        $path = $info->getSubPathname();
        if ($info->isDot()) {
            continue;
        }
        $dest_path = $dest . '/' . $path;
        if ($info->isDir()) {
            mkdir($dest_path);
        } else {
            copy($info->getPathname(), $dest_path);
        }
        chown($dest_path, $entry['uid']);
        chgrp($dest_path, $entry['gid']);
    }
}

function install_plugins() {
    global $stderr, $workdir, $plugindir;
    foreach (new DirectoryIterator($plugindir) as $info) {
        if ($info->isDot() || !$info->isDir()) {
            continue;
        }
        $plugin = $info->getFilename();
        fwrite($stderr, "Installing plugin {$plugin}...\n");
        fflush($stderr);
        recursive_copy($info->getPathname(), $workdir . '/app/Plugin/' . $plugin);
        run(['app/console', 'plugin:develop', 'install', '--code', $plugin]);
        run(['app/console', 'plugin:develop', 'enable', '--code', $plugin]);
    }
}

run([$workdir . '/eccube_install.php', $dbtype, 'none']);
install_plugins();
HERE
fi

exec /usr/local/bin/docker-php-entrypoint $@
