<?php
require_once __DIR__ . '/Server.php';
require_once __DIR__ . '/Method.php';

/**
 * 错误处理器
 * @param $error_level
 * @param $error_message
 * @param $error_file
 * @param $error_line
 * @param $error_context
 */
function error_handler($error_level,$error_message,$error_file,$error_line,$error_context)
{
    $msg = "[Msg]" . $error_message . "\n[File]" . json_encode($error_file) . "\n[Line]" . json_encode($error_line) . "\n[Context]" . json_encode($error_context);
    switch ($error_level)
    {
        case 2:
            Method::log(3,$msg);
            break;
        case 8:
            Method::log(2,$msg);
            break;
        case 256:
            Method::log(4,$msg);
            break;
        case 512:
            Method::log(3,$msg);
            break;
        case 1024:
            Method::log(2,$msg);
            break;
        case 4096:
            Method::log(4,$msg);
            break;
        case 8191:
            Method::log(4,$msg);
            break;
    }
}
set_error_handler('error_handler');

$ws = new Server();
$ws->start();