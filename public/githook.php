<?php
ini_set('display_errors', 'on');
error_reporting(E_ALL);
function addlog($content)
{
    file_put_contents(__DIR__ . "/pull.log", date('Y-m-d H:i:s', time()) . ' ' . $content . chr(10), FILE_APPEND);
}

addlog("Git Start");
try {
    $cmd = 'cd ' . __DIR__ . ' && /usr/local/git/bin/git pull origin master 2>&1';
    $result=shell_exec($cmd);
    echo '<pre>'.$result.'</pre>';
    addlog('shell_exec output:' . PHP_EOL . $result);
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
} catch (Exception $e) {
    addlog("Caught exception: " . $e->getMessage());
}
addlog("Git End");