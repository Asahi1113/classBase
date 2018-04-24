<?php
namespace curl;
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/24
 * Time: 18:15
 */
include 'Curl.class.php';
$curl = new Curl();
$header = array(
    'CLIENT-IP:111.11.111.11',
    'X-FORWARDED-FOR:111.11.111.11',
);
$curl->setHeader($header);
$curl->get('http://demo.cnyotc.cn/test/test.php');
echo $curl->response;