<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/28
 * Time: 16:51
 */
header('Connect-type:text/html;charset=utf-8');
$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$last_error = socket_last_error();
if($socket === false)
{
    var_dump($last_error);

    print_r(socket_strerror(socket_last_error()));
}
if (false == (@socket_bind($socket, '127', 80))) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
}