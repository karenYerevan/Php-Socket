<?php

namespace Socket;
/**
 * Description of Socket
 *
 * @author Karen
 */
class Socket {

    protected $host = "127.0.0.1"; //localhost
    protected $port = "9090";
    protected $nul = NULL;
    protected $socket;
    private $loopI = 0;
    /**
     * Description 
     * [0] => 'Sec-WebSocket-Key'
     */
    protected $clientsecKey = array();

    /**
     * Description 
     * [Sec-WebSocket-Key] => 'Resuarse'
     */
    protected $clientSecResuarse = array();

    public function __construct() 
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->socket, 0, $this->port);
        socket_listen($this->socket);
        echo "Connect";
    }
    
    // generate header
    protected function performHandshaking($receved_header,$client_conn, $host, $port)
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
        //hand shaking header
        $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "WebSocket-Origin: $host\r\n" .
        "WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
        "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
        
        $this->loopI++;
        $this->clientSecResuarse[$secAccept] = $client_conn;
        $this->clientsecKey[$this->loopI] = $secAccept;
        socket_write($client_conn,$upgrade,strlen($upgrade));

        return $this->clientsecKey;
    }
    
    //Unmask incoming framed message
    protected function unmask($text) 
    {

        $length = ord($text[1]) & 127;
        if ($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        } elseif ($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        } else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }

    //Encode message for transfer to client.
    protected function mask($text)
    {
        //var_dump($text);
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if ($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        elseif ($length >= 65536)
            $header = pack('CCNN', $b1, 127, $length);
        return $header . $text;
    }
    
    protected function sendMessage($msg)
    {

        foreach($this->clients as $changed_socket)
        {
            @socket_write($changed_socket,$msg,strlen($msg));
        }
        return true;
    }

}
