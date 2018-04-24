<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/23
 * Time: 9:57
 */

//获取关注列表
require 'Curl.class.php';
//$url = 'https://api.weibo.cn/2/friendships/friends?aid=01AgU4MnKuHgoQbY_V4f3RJRHSU6YHpSmCjRNBPW1G1_DHDIY.&c=weicoabroad&count=50&from=1229793010&gsid=_2A253eWCBDeRxGeNL71sS-SjFwj2IHXVSL_NJrDV6PUJbkdANLUjBkWpNSOsg_ncqQ8zxM_R-XzG4n9LuhUUI-EQI&i=0d21a5c&lang=zh_CN&real_relationships=1&s=c2608fa0&trim_status=1&ua=iPhone7%2C2_iOS11.3_Weibo%20intl._2970_wifi&uid=5549396991&v_p=50';
//添加关注
//$url = 'https://api.weibo.cn/2/friendships/create';
//取消关注
//$url = 'https://api.weibo.cn/2/friendships/destroy';
//环球时报
//$postData = 'aid=01AgU4MnKuHgoQbY_V4f3RJRHSU6YHpSmCjRNBPW1G1_DHDIY.&c=weicoabroad&f=2&from=1229993010&gsid=_2A253eWCBDeRxGeNL71sS-SjFwj2IHXVSL_NJrDV6PUJbkdANLUjBkWpNSOsg_ncqQ8zxM_R-XzG4n9LuhUUI-EQI&i=0d21a5c&lang=zh_CN&s=c2608fa0&ua=iPhone7%2C2_iOS11.3_Weibo%20intl._2990_wifi&uid=1974576991&v_p=50';
//$postData = 'aid=01AgU4MnKuHgoQbY_V4f3RJRHSU6YHpSmCjRNBPW1G1_DHDIY.&c=weicoabroad&from=1229993010&gsid=_2A253eWCBDeRxGeNL71sS-SjFwj2IHXVSL_NJrDV6PUJbkdANLUjBkWpNSOsg_ncqQ8zxM_R-XzG4n9LuhUUI-EQI&i=0d21a5c&lang=zh_CN&s=c2608fa0&ua=iPhone7%2C2_iOS11.3_Weibo%20intl._2990_wifi&uid=1974576991&v_p=50';

$curl = new Curl();
//$url = 'https://elevate.ink/mdc_20160207HH/auth/login';
//$curl->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0');
//$curl->setCookie('UM_distinctid=162a98a133a430-0cdae77d5e7d07-4c322073-144000-162a98a133c8a6; XSRF-TOKEN=eyJpdiI6ImhMUndvQUJPZ01qRUxUbEtpWEQ0aFE9PSIsInZhbHVlIjoiTkIxZEo2QTdiUEVVUnBNUnprUFJDTlJZZEJIcCtXZ29lc0JJREtYKzJvY3hDbDlBNzAxak5qTENrT0ZLNXM5dmFQODFxU1IyNW9CTDQ5NjB1U01cL3dBPT0iLCJtYWMiOiI1ODcwOWY2MTNiMzUwZTE3YzM2N2QzYmYwMDBkYjBjNDE3MmM2NzFmZGM2OWMyOGU3NWNkYjczZmYxOGVmNGJiIn0=');
//$postData = '_token=cjIunFSYjulsAXVyklO11XKx9iaJFO2xKdjVsBF5&password=123456&username=root';
$url = 'https://finance.yahoo.com/webservice/v1/symbols/allcurrencies/quote';
$curl->get($url);
if($curl->error)
{
    var_dump($curl->error_message);
}
else
{
    print_r($curl->response);
}
