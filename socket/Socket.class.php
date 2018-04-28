<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/28
 * Time: 16:22
 */
namespace socket;
class Socket
{
    const INET = AF_INET;           //IPv4 网络协议
    const INET6 = AF_INET6;         //IPv6 网络协议
    const UNIX = AF_UNIX;           //本地通讯协议。具有高性能和低成本的 IPC（进程间通讯）。
    const STREAM = SOCK_STREAM;     //提供一个顺序化的、可靠的、全双工的、基于连接的字节流。支持数据传送流量控制机制。TCP 协议即基于这种流式套接字。
    const DGRAM = SOCK_DGRAM;       //提供数据报文的支持。(无连接，不可靠、固定最大长度).UDP协议即基于这种数据报文套接字。
    const SEQPACKET = SOCK_SEQPACKET;       //提供一个顺序化的、可靠的、全双工的、面向连接的、固定最大长度的数据通信；数据端通过接收每一个数据段来读取整个数据包。
    const RAW = SOCK_RAW;           //提供读取原始的网络协议。这种特殊的套接字可用于手工构建任意类型的协议。一般使用这个套接字来实现 ICMP 请求（例如 ping）。
    const RDM = SOCK_RDM;           //提供一个可靠的数据层，但不保证到达顺序。一般的操作系统都未实现此功能。
    const SO_UDP = SOL_UDP;
    const SO_TCP = SOL_TCP;

    private $last_error = 0;
    private $error = null;

    private function init()
    {
        $socket = socket_create(self::INET,self::STREAM,self::SO_TCP);
    }
}