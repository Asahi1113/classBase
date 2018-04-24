<?php
namespace curl;
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/23
 * Time: 11:23
 */
class Curl
{
    const USER_AGENT = 'PHP Curl/1.6 (+https://github.com/php-mod/curl)';

    private $_cookies;
    private $_headers;

    public $curl;
    public $response = null;

    public $error = false;
    public $error_code = 0;
    public $error_message = null;
    public $curl_error = false;
    public $curl_error_code = 0;
    public $curl_error_message = null;
    public $http_error = false;
    public $http_status_code = 0;
    public $http_error_message = null;

    public $request_headers = null;
    public $response_headers = [];

    protected $response_header_continue = false;

    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('The cURL extensions is not loaded, make sure you have installed the cURL extension: https://php.net/manual/curl.setup.php');
        }
        $this->init();
    }

    private function init()
    {
        $this->curl = curl_init();
        $this->setUserAgent(self::USER_AGENT);
        $this->setOpt(CURLINFO_HEADER_OUT,true);        //TRUE 时追踪句柄的请求字符串
        $this->setOpt(CURLOPT_HEADER,false);         //启用时会将头文件的信息作为数据流输出。
        $this->setOpt(CURLOPT_RETURNTRANSFER,true);     //TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出
        $this->setOpt(CURLOPT_HEADERFUNCTION, array($this, 'addResponseHeaderLine'));
        return $this;

    }

    public function addResponseHeaderLine($curl, $header_line)
    {
        $trimmed_header = trim($header_line, "\r\n");

        if ($trimmed_header === "") {
            $this->response_header_continue = false;
        } else if (strtolower($trimmed_header) === 'http/1.1 100 continue') {
            $this->response_header_continue = true;
        } else if (!$this->response_header_continue) {
            $this->response_headers[] = $trimmed_header;
        }

        return strlen($header_line);
    }
    protected function exec()
    {
        $this->response = curl_exec($this->curl);

        $this->curl_error_code = curl_errno($this->curl);
        $this->curl_error_message = curl_error($this->curl);
        $this->curl_error = !($this->curl_error_code === 0);
        $this->http_status_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->http_error = in_array(floor($this->http_status_code / 100), array(4, 5));
        $this->error = $this->curl_error || $this->http_error;
        $this->error_code = $this->error ? ($this->curl_error ? $this->curl_error_code : $this->http_status_code) : 0;
        $this->request_headers = preg_split('/\r\n/', curl_getinfo($this->curl, CURLINFO_HEADER_OUT), null, PREG_SPLIT_NO_EMPTY);
        $this->http_error_message = $this->error ? (isset($this->response_headers['0']) ? $this->response_headers['0'] : '') : '';
        $this->error_message = $this->curl_error ? $this->curl_error_message : $this->http_error_message;

        return $this->error_code;
    }
    public function setOpt($option,$value)
    {
        return curl_setopt($this->curl,$option,$value);
    }
    private function isHttps($url)
    {
        if(stripos($url,'https:') !== false)
        {
            $this->setOpt(CURLOPT_SSL_VERIFYPEER, false);    //设置为发送https请求
        }
        $this->setOpt(CURLOPT_URL,$url);
        return $this;
    }
    public function get($url)
    {
        $this->isHttps($url);
        $this->exec();
        return $this;
    }
    public function post($url,$data)
    {
        $this->isHttps($url);
        $this->preparePayload($data);
        $this->exec();
        return $this;
    }
    public function preparePayload($data)
    {
        $this->setOpt(CURLOPT_POST,true);
        if(is_array($data) || is_object($data)){
            $data = http_build_query($data);
        }
        $this->setOpt(CURLOPT_POSTFIELDS,$data);
    }
    public function setUserAgent($useragent)
    {
        $this->setOpt(CURLOPT_USERAGENT,$useragent);
        return $this;
    }
    public function setCookie($cookie)
    {
        $this->_cookies = $cookie;
        $this->setOpt(CURLOPT_COOKIE,$cookie);
        return $this;
    }

    public function setHeader($header)
    {
        $this->_headers = $header;
        $this->setOpt(CURLOPT_HTTPHEADER,$this->_headers);
        return $this;
    }
    public function close()
    {
        if(is_resource($this->curl)){
            curl_close($this->curl);
        }
        return $this;
    }
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->close();
    }
}