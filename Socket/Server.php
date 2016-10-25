<?php

namespace Socket;

use Socket\Socket;
/**
 * Description of server
 *
 * @author Karen
 */
class Server extends Socket{
    
    public $clients = array();
    public $chenge = array();
    
    public function __construct() 
    {
        parent::__construct();
        $this->clients = array($this->socket);
    }
    
    public function run() 
    {
        while(true){
            $this->chenge = $this->clients;
            socket_select($this->chenge, $this->nul, $this->nul, 0, 10);
            if(in_array($this->socket, $this->chenge)){
                $socket_new = socket_accept($this->socket);
                $this->clients[] = $socket_new;
                $header = socket_read($socket_new, 1024);
                $this->performHandshaking($header, $socket_new, $this->host, $this->port);
                socket_getpeername($socket_new, $ip);
                
                $response = $this->mask(json_encode(array('type'=>'system', 'message'=>$ip.' connected'))); 
                $this->sendMessage($response);
                $found_socket = array_search($this->socket, $this->chenge);
                unset($this->chenge[$found_socket]);
            }
            
            foreach ($this->chenge as $changed_socket) {
                
                while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
                {
                    
                    $received_text = $this->unmask($buf); 
                    $tst_msg = json_decode($received_text);
                    if(is_null($tst_msg)){                      // Remove user
                        $foundKeyForClient = array_search($changed_socket, $this->clients);
                        unset($this->clients[$foundKeyForClient]);
                        
                        $foundResClient = array_search($changed_socket, $this->clientSecResuarse);
                        unset($this->clientSecResuarse[$foundResClient]);
                        
                        $foundSecKeyClient = array_search($foundResClient, $this->clientsecKey);
                        unset($this->clientsecKey[$foundSecKeyClient]);
                        
                        break;
                    }else{
                        $response_text = $this->mask(json_encode(array('type'=>'sms', 'name'=>$tst_msg->name, 'text'=>$tst_msg->text)));
                        $this->sendMessage($response_text); 
                        break;
                    }
                    
                    break;   
                }
                
            }
            
        }
        
    }
    
}
