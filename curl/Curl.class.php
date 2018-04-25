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
    const AUTH_BASIC = CURLAUTH_BASIC;
    const AUTH_DIGEST = CURLAUTH_DIGEST;
    const AUTH_GSSNEGOTIATE = CURLAUTH_GSSNEGOTIATE;
    const AUTH_NTLM = CURLAUTH_NTLM;
    const AUTH_ANY = CURLAUTH_ANY;
    const AUTH_ANYSAFE = CURLAUTH_ANYSAFE;
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3371.0 Safari/537.36';

    private $_cookies = [];
    private $_headers = [];

    public $curl;

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

    public $response = null;
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
    //不建议直接调用exec()
    public function _exec()
    {
        return $this->exec();
    }
    public function setOpt($option,$value)
    {
        return curl_setopt($this->curl,$option,$value);
    }
    public function getOpt($option)
    {
        return curl_getinfo($this->curl,$option);
    }
    public function getEndpoint()
    {
        return $this->getOpt(CURLINFO_EFFECTIVE_URL);   //最后一个有效的URL地址
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
    protected function preparePayload($data)
    {
        $this->setOpt(CURLOPT_POST,true);
        if(is_array($data) || is_object($data)){
            $data = http_build_query($data);
        }
        $this->setOpt(CURLOPT_POSTFIELDS,$data);
    }

    public function setHeader($key,$value)
    {
        $this->_headers[$key] = $key.': '.$value;
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->_headers));
        return $this;
    }
    public function setCookie($key,$value)
    {
        $this->_cookies[$key] = $value;
        $this->setOpt(CURLOPT_COOKIE,http_build_query($this->_cookies,'','; '));
        return $this;
    }
    public function setUserAgent($useragent)
    {
        $this->setOpt(CURLOPT_USERAGENT,$useragent);
        return $this;
    }
    public function setReferer($referer)
    {
        $this->setOpt(CURLOPT_REFERER,$referer);
        return $this;
    }

    public function setBasicAuthentication($username,$password)
    {
        $this->setHttpAuth(self::AUTH_BASIC);
        $this->setOpt(CURLOPT_USERPWD,$username.':'.$password);
        return $this;
    }
    protected function setHttpAuth($httpauth)
    {
        $this->setOpt(CURLOPT_HTTPAUTH,$httpauth);      //使用的http验证方法
    }
    public function restat()
    {
        $this->close();
        $this->_cookies = [];
        $this->_headers = [];
        $this->error = false;
        $this->error_code = 0;
        $this->error_message = null;
        $this->curl_error = false;
        $this->curl_error_code = 0;
        $this->curl_error_message = null;
        $this->http_error = false;
        $this->http_status_code = 0;
        $this->http_error_message = null;
        $this->request_headers = null;
        $this->response = null;
        $this->response_headers = [];
        $this->init();
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
    public function isInfo()
    {
        return $this->http_status_code >= 100 && $this->http_status_code < 200;      //100-199,用于指定客户端相应的某些动作
    }
    public function isSuccess()
    {
        return $this->http_status_code >= 200 && $this->http_status_code < 300;
    }
    public function isRedirect()
    {
        return $this->http_status_code >= 300 && $this->http_status_code < 400;
    }
    public function isClientError()
    {
        return $this->http_status_code >= 400 && $this->http_status_code < 500;
    }
    public function isServerError()
    {
        return $this->http_status_code >= 500 && $this->http_status_code < 600;
    }
    public function isError()
    {
        return $this->http_status_code >= 400 && $this->http_status_code < 600;
    }
}