<!doctype html>
<html lang="en">
    <head>
        <title>Petlox</title>
        <meta charset="utf-8">
        <link rel="stylesheet" type="text/css" href="/assets/css/style.css" media="all" />
        <script type="text/javascript" src="/assets/javascript/jquery.js"></script>
    </head>
    <body>
        <div class="content">
            <div class="bord"></div>
            <input type="text" id="name" class="my_input" placeholder="Name">
            <textarea type="text" id="text" class="my_input" placeholder="Type text..."></textarea>
            <button id="send">Send</button>
            <button id="connect">Connect</button>
        </div>
    </body>
</html>
<script>
    $(document).ready(function(){
        var socket;
        $('#connect').click(function(){
            socket = new WebSocket("ws://localhost:9090");
            socket.onopen = function(ev){
                $(".bord").append("<div>Connected!!!</div>");
            }
            $(this).css({display:'none'});
            $('#send').css({display:'block'});
            getMes(socket);
        });
        
        $("#send").click(function(){

            var sendParam = {
                name : $('#name').val() || 'Some name',
                text : $('#text').val() || 'Some text'
            };
            socket.send(JSON.stringify(sendParam));
            
            $('#text').val('')
        })
        
    });
    
function getMes(sockets){
    sockets.onmessage = function(ev){
        var msg = JSON.parse(ev.data);

        var name = msg.name;
        var type = msg.type; 
        var text = msg.text;  

        if(type == 'sms'){
            $(".bord").append("<div class='main_mess'><div class='user_name'>Name:"+name+"</div><div class='user_text'>Text:"+text+"</div></div>");
        }
    }
    
    sockets.onerror = function(ev){$('.bord').append("<div>Error Occurred - "+ev.data+"</div>");}; 
    sockets.onclose = function(ev){$('.bord').append("<div>Connection Closed</div>");}; 
}    
</script>       