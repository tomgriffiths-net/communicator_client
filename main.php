<?php
class communicator_client{
    public static function runfunction(string $function, string $ip="127.0.0.1", int $port=8080, bool $returnErrorString=false):mixed{

        $result = self::run($ip, $port, ["type"=>"function_string", "payload"=>$function]);

        if($result["success"]){
            return $result["result"];
        }

        if(isset($result['error']) && is_string($result['error'])){
            if($returnErrorString){
                return $result['error'];
            }
            else{
                mklog(0, 'Runfunction Error: ' . $result['error']);
            }
        }

        return false;
    }
    public static function runcommand(string $command, string $ip="127.0.0.1", int $port=8080, bool $returnOutput=false):bool|string{

        $result = self::run($ip, $port, ["type"=>"command" . ($returnOutput ? "_output" : ""), "payload"=>$command]);

        if($returnOutput){
            if(is_string($result['result'])){
                return $result['result'];
            }
            else{
                return "";
            }
        }

        return $result["success"];
    }
    public static function serverCalledMe():bool{
        foreach(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $caller){
            if(isset($caller['class']) && $caller['class'] === "communicator_server"){
                return true;
            }
        }

        return false;
    }
    public static function run(string $ip, int $port, array $data, float|false $timeout=false):array{

        $data['version'] = 2;

        //Check if call originates from communicator_server to avoid communicator talking to itself while the server is in a busy state
        if(self::serverCalledMe()){
            mklog(0, "Running communicator_server action internally");
            if(method_exists('communicator_server', 'run')){
                return communicator_server::run($data);
            }
            else{
                return ["success"=>false, "error"=>"Cannot connect to communicator_server from within communicator_server with version 11 or lower"];
            }
        }
        else{
            $socket = communicator::connect($ip, $port, $timeout, $socketError, $socketErrorString);

            if($socket === false){
                return [
                    "success" => false,
                    "error" => "Unable to connect to " . $ip . ":" . $port
                ];
            }
            
            $result = self::execute($socket, $data);

            if(!communicator::close($socket)){
                mklog(2, "Failed to close socket");
            }

            return $result;
        }
    }
    private static function execute($socket, array $data):array{

        if(!isset($data['type'])){
            return ["success"=>false, "error"=>"Type not set"];
        }

        if(!isset($data['payload'])){
            return ["success"=>false, "error"=>"Payload not set"];
        }

        if(!communicator::sendData($socket, $data, true)){
            return ["success"=>false, "error"=>"Error sending data"];
        }

        $result = communicator::receiveData($socket, true);
        if(!is_array($result)){
            return ["success"=>false, "error"=>"Error receiving data"];
        }

        if(!isset($result['success']) || !isset($result['result'])){
            return ["success"=>false, "error"=>"Received incomplete data"];
        }

        return $result;
    }
}