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
    'X-FORWARDED-FOR:123.456.789.001',
    'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
    'User-Agent:www.google.com',
    'Accept-Encoding: gzip, deflate'
);
$curl->setHeader('CLIENT-IP','111.11.111.11');
$curl->setHeader('X-FORWARDED-FOR','123.456.789.119');
$curl->setCookie('PHPSESSID','a0ku3d621amg6f3dao2jllaea7');
$curl->setReferer('localhost/demo.php');
$curl->get('http://demo.cnyotc.cn/test/test.php');

echo $curl->response;
echo '<br/><hr/>';
print_r($curl->getOpt(CURLINFO_HEADER_OUT));
