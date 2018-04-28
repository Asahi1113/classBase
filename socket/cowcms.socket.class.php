<?PHP
class Socket{
    private $host = '0.0.0.0';//服务器地址
    private $port = 0;//端口
    private $main_socket = 0;//主进程
    private $max_room_user = 0;//每个房间最大链接用户
    private $max_total_user = 0;//总链接用户数
    private $root_id = 0;//房间id
    private $clients = array();//连接的客户端
    private $null = NULL;
    private $users = array(); //链接用户信息

    private $user_key =""; //当前活动用户key

    public $onMessage = NULL;
    public $onClose = NULL;
    public $onConnect = NULL;
    public $onError = NULL;
    public $onSet = NULL; //设置用户信息
    public $onSetBefore = NULL; //设置用户之前
    public $onPing = NULL; //有心跳信息
    public $onOverTotal = NULL; //超出总人数
    public $onOverRoom = NULL;
    public $setOpen = NULL;

    function __construct($host, $port, $max_total_user=0,$max_root_user=0) {
        $this->host = $host;
        $this->port = $port;
        $this->max_total_user = $max_total_user;
        $this->max_root_user = $max_root_user;
    }
    /*=================================================
    //创建socket
    ==================================================*/
    function create_socket() {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, TRUE);
        socket_bind($socket, $this->host, $this->port);
        socket_listen($socket, $this->max_total_user);
        return $socket;
    }
    /*=================================================
    //挂起socket
    ==================================================*/
    function start_server() {
        $this->main_socket=$this->create_socket();
        $this->clients = array($this->main_socket);
        $null=NULL;
        $open=true;

        while(1) {
            if($this->setOpen)
            {
                $open=call_user_func_array($this->setOpen,array($open));
                if(!$open) break;
            }
            $activity=$this->clients;
            socket_select($activity,$null,$null,0);
            foreach ($activity as $k => $socket) {
                if($this->add_client($socket)!==false) {
                    continue;
                }

                if (@socket_recv($socket, $data, 1024, 0) && $data) {//没消息的socket就跳过
                    $this->user_key=$k;
                    $msg = json_decode($this->unmask($data),true);

                    $action = $msg['action'];
                    if($msg)
                    {
                        switch ($action)
                        {
                            case 'close'://关闭用户连接
                                $user_list=$this->get_user_list('close',0,$k);
                                if($this->onClose)  call_user_func_array($this->onClose, array($k,$user_list,$this));
                                $this->close($k);
                                break;
                            case 'set'://设置连接用户信息
                                $user_list=$this->get_user_list('room_all',$msg['room_id']);
                                $room_user_count=$user_list?count($user_list):0;
                                $max_room_user=$this->max_room_user;
                                if($this->onOverRoom && $room_user_count>=$max_room_user)
                                {
                                    $return=call_user_func_array($this->onOverRoom,array($k,$room_user_count,$max_room_user,$this));
                                    if(!$return)
                                    {
                                        $this->close($k);
                                        continue;
                                    }
                                }

                                if($this->onSetBefore)
                                {
                                    $return=call_user_func_array($this->onSetBefore,array($k,$room_user_count,$msg,$this));
                                    if(!$return)
                                    {
                                        $this->close($k);
                                        continue;
                                    }
                                }

                                $this->del_key($msg['name']);
                                socket_getpeername($socket,$ip);
                                $user['room_id']=$msg['room_id'];
                                $user['name']=$msg['name'];
                                $user['uid']=$msg['uid'];
                                $user['ip']=$msg['ip'];
                                $this->users[$k]=$user;
                                $user_list=$this->get_user_list('room_all',$msg['room_id']);

                                if($this->onSet) call_user_func_array($this->onSet,array($this->users[$k],$msg,$user_list,$this));
                                break;
                            case 'ping':
                                if($this->onPing) call_user_func_array($this->onPing, array($k,$msg,$this));
                                break;
                            default://给用户发信息
                                if($this->onMessage) call_user_func_array($this->onMessage, array($this->users[$k],$msg,$this));
                        }

                    }
                    continue;
                }
                else
                {
                    $user_list=$this->get_user_list('close',0,$k);
                    if($this->onClose)  call_user_func_array($this->onClose, array($this->users[$k],$k,$user_list,$this));
                    $this->close($k);
                    continue;
                }
            }

        }
        socket_close($this->main_socket);
    }

    /*=================================================
    创建新客户
    ==================================================*/
    function add_client($socket) {

        if($socket !== $this->main_socket) return false;
        $clients=$this->clients;
        $max_total_user=$this->max_total_user;

        $accept = socket_accept($this->main_socket);
        if ($accept < 0) return false;

        $header = socket_read($accept,1024);

        socket_getpeername($accept,$ip);

        $key=md5(uniqid());
        $this->clients[$key] = $accept;

        $this->perform_handshaking($header,$accept);
        if($this->onConnect) call_user_func_array($this->onConnect,array($key,$ip,$this));

        if($this->onOverTotal && count($clients)>=$max_total_user)
        {
            $return=call_user_func_array($this->onOverTotal,array($key,$max_total_user,$this));
            if(!$return)
            {
                $this->close($key);
                return false;
            }
        }
        return true;
    }

    /*=================================================
    关闭客户
    ==================================================*/
    function close($key) {
        $socket=$this->clients[$key];
        socket_close($socket);
        unset($this->clients[$key]);
        unset($this->users[$key]);
    }
    /*=================================================
    发送信息
    $type user_all给所有房间用户发信息  user_room_all给当前房间用户发信息 user_room_no 给当前房间发信息，不带发信人用户信息，user_prompt给某个用户发提示信息 by_uid依靠uid给用户发信息($to_key 为用户id $room_id 为房间id)
    $client_id  给某个人发信息的用户key值
    $room_id 要给没个房间的用户发信息的房间ID
    ==================================================*/
    function send($msg,$type='user_room_all',$key=0,$to_key='',$room_id=0)
    {
        $key=$key?$key:$this->user_key;
        if(!$key || !$msg) return false;
        $users=$this->users;
        $clients=$this->clients;
        $current_user=$users[$key]; //当前活动用户

        $room_id=$room_id?$room_id:$current_user['room_id'];


        $msg['f_key']=$key;
        $msg['f_uid']=$current_user['uid'];
        $msg['f_name']=$current_user['name'];
        $msg['f_ip']=$current_user['ip'];
        $msg['f_room_id']=$current_user['room_id'];

        switch ($type)
        {
            case 'user_all'://显示所以用户
                foreach($clients as $k=>$client)
                {
                    $msg['t_key']=$k;
                    $msg['t_uid']=$users[$k]['uid'];
                    $msg['t_name']=$users[$k]['name'];
                    $msg['t_ip']=$users[$k]['ip'];
                    $msg['t_room_id']=$users[$k]['room_id'];
                    $send_msg=$this->mask(json_encode($msg));
                    @socket_write($client,$send_msg,strlen($send_msg));
                }
                break;
            case 'user_room_all'://显示当前房间用户
                foreach($clients as $k=>$client)
                {
                    if($users[$k]['room_id']==$room_id)
                    {
                        $msg['t_key']=$k;
                        $msg['t_uid']=$users[$k]['uid'];
                        $msg['t_name']=$users[$k]['name'];
                        $msg['t_ip']=$users[$k]['ip'];
                        $msg['t_room_id']=$users[$k]['room_id'];
                        $send_msg=$this->mask(json_encode($msg));
                        @socket_write($client,$send_msg,strlen($send_msg));
                    }
                }
                break;
            case 'user_room_no':
                foreach($clients as $k=>$client)
                {
                    if($users[$k]['room_id']==$room_id)
                    {
                        $send_msg=$this->mask(json_encode($msg));
                        @socket_write($client,$send_msg,strlen($send_msg));
                    }
                }
                break;
            case 'user_prompt': //给发送者返回提示信息
                $send_msg=$this->mask(json_encode($msg));
                @socket_write($clients[$key],$send_msg,strlen($send_msg));
                break;
            case 'by_uid': //给发送者返回提示信息
                foreach($clients as $k=>$client)
                {
                    if($users[$k]['uid']==$to_key && $users[$k]['room_id']==$room_id)
                    {
                        $send_msg=$this->mask(json_encode($msg));
                        @socket_write($client,$send_msg,strlen($send_msg));
                        break;
                    }
                }
                break;
            case 'user_no': //除活动用户外的同房间用户
                foreach($clients as $k=>$client)
                {
                    if($k!=$key && $users[$k]['room_id']==$room_id)
                    {
                        $send_msg=$this->mask(json_encode($msg));
                        @socket_write($client,$send_msg,strlen($send_msg));
                        break;
                    }
                }
                break;
            default://给单个用户发
                $msg['t_id']=$to_key;
                $msg['t_uid']=$users[$to_key]['uid'];
                $msg['t_name']=$users[$to_key]['name'];
                $msg['t_ip']=$users[$to_key]['ip'];
                $msg['t_room_id']=$users[$to_key]['room_id'];
                $send_msg=$this->mask(json_encode($msg));
                @socket_write($clients[$to_key],$send_msg,strlen($send_msg));
                @socket_write($clients[$key],$send_msg,strlen($send_msg));

        }
        return true;
    }

    /*=================================================
    获取聊天室用户列表
    $type 获取当前房间用户列表  all 获取全部房间用户列表
    ==================================================*/
    function get_user_list($type='room_all',$room_id=0,$key=0)
    {
        $key=$key?$key:$this->user_key;
        $users=$this->users;
        $clients=$this->clients;
        $current_user=$users[$key]; //当前活动用户
        $room_id=$room_id?$room_id:$current_user['room_id'];

        switch ($type)
        {
            case 'all':
                foreach($clients as $k=>$client)
                {
                    $user_list[$k]=$users[$k]['name'];
                }
                break;
            case 'room_all':
                foreach($clients as $k=>$client)
                {
                    if($users[$k]['room_id']==$room_id)
                    {
                        $user_list[$k]=$users[$k]['name'];
                    }
                }
                break;
            case 'close':
                foreach($clients as $k=>$client)
                {
                    if($key!=$k && $users[$k]['room_id']==$room_id)
                    {
                        $user_list[$k]=$users[$k]['name'];
                    }
                }
                break;
            default:
                foreach($clients as $k=>$client)
                {
                    if($users[$k]['room_id']==$room_id)
                    {
                        $user_list[$k]=$users[$k]['name'];
                    }
                }
        }
        return $user_list;
    }
    /*=================================================
    握手逻辑
    ==================================================*/
    function perform_handshaking($receved_header,$client_conn)
    {
        $headers = array();
        $lines = preg_split("/\r\n/", $receved_header);
        foreach($lines as $line)
        {
            $line = chop($line);
            if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
            {
                $headers[$matches[1]] = $matches[2];
            }
        }

        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: ".$this->host."\r\n" .
            "WebSocket-Location: ws://".$this->host.":".$this->port."/index.php/chat/Index/index.php\r\n".
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
        socket_write($client_conn,$upgrade,strlen($upgrade));
    }

    /*=================================================
    数据解码
    ==================================================*/
    function unmask($text) {
        $length = ord($text[1]) & 127;
        if($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        }
        elseif($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        }
        else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i%4];
        }
        return $text;
    }

    /*=================================================
    数据编码
    ==================================================*/
    function mask($text)
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        elseif($length >= 65536)
            $header = pack('CCNN', $b1, 127, $length);
        return $header.$text;
    }
    /*=================================================
    写日志文件
    ==================================================*/
    function log_($content,$filename="socket.txt") {
        /*		    $unit=array('b','kb','mb','gb','tb','pb');
                    $content=round($content/pow(1024,($i=floor(log($content,1024)))),2).' '.$unit[$i]; */
        file_put_contents($filename,$content."|",FILE_APPEND);
    }

    /*=================================================
    获取key
    ==================================================*/
    function del_key($name) {
        foreach($this->users as $k=>$v)
        {
            if($v['name']==$name) $this->close($k);
        }
    }
}

?>