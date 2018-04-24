<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/24
 * Time: 18:14
 */

echo getenv('HTTP_CLIENT_IP');
echo getenv('HTTP_X_FORWARDED_FOR');
echo getenv('REMOTE_ADDR');
