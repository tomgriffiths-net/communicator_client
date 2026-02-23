<?php
class communicator_client{
    public static function runfunction(string $function, string $ip="127.0.0.1", int $port=8080, bool $returnErrorString=false):mixed{

        $result = self::run($ip, $port, ["type"=>"function_string", "payload"=>$function]);

        if($result["success"]){
            return $result["result"];
        }

        if(isset($result['error']) && is_string($result['error']) && $returnErrorString){
            return $result['error'];
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
    public static function uploadFile(string $serverFileName, string $localFileName, bool $overwrite, string $ip, int $port=8080):string|false{
        $result = self::run($ip, $port, [
            "type" => "fileup",
            "payload" => [
                'name' => $serverFileName,
                'clientFile' => $localFileName,
                'overwrite' => $overwrite
            ]
        ]);

        if($result["success"]){
            return $result["result"];
        }

        return false;
    }
    public static function downloadFile(string $serverFileName, string $localFileName, bool $overwrite, string $ip, int $port=8080):string|false{
        $result = self::run($ip, $port, [
            "type" => "filedown",
            "payload" => [
                'name' => $serverFileName,
                'clientFile' => $localFileName,
                'overwrite' => $overwrite
            ]
        ]);

        return $result["success"];
    }
    public static function customAction(string $package, string $action, array $args=[], string $ip="127.0.0.1", int $port=8080):mixed{
        $result = self::run($ip, $port, [
            "type" => "custom",
            "payload" => [
                'package' => $package,
                'action' => $action,
                'args' => $args
            ]
        ]);

        if($result["success"]){
            return $result["result"];
        }

        return false;
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

        $data['version'] = 4;

        $result = [];

        //Check if call originates from communicator_server to avoid communicator talking to itself while the server is in a busy state
        if(self::serverCalledMe()){
            mklog(0, "Running communicator_server action internally");
            if(!method_exists('communicator_server', 'run')){
                $result = ["success"=>false, "error"=>"Cannot connect to communicator_server from within communicator_server with version 11 or lower"];
                goto ret;
            }

            $result = communicator_server::run($data);
            goto ret;
        }
        
        $socket = communicator::connect($ip, $port, $timeout, $socketError, $socketErrorString);

        if($socket === false){
            $result = ["success"=>false, "error"=>"Unable to connect to " . $ip . ":" . $port];
            goto ret;
        }
        
        $result = self::execute($socket, $data);

        if(!communicator::close($socket)){
            mklog(2, "Failed to close socket");
        }

        ret:

        if(isset($result['error'])){
            mklog(1, 'Communicator error: ' . $result['error']);
        }

        return $result;
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

        if($data['type'] === "fileup" || $data['type'] === "filedown"){
            if(!isset($data['payload']['clientFile']) || !is_string($data['payload']['clientFile'])){
                return ["success"=>false, "error"=>"Client File not set"];
            }

            if(!isset($data['payload']['overwrite'])){
                $data['payload']['overwrite'] = false;
            }

            if($data['type'] === "fileup"){
                if(!communicator::sendFromFile($socket, $data['payload']['clientFile'], true)){
                    return ["success"=>false, "error"=>"Failed to upload file"];
                }
            }
            else{//filedown
                if(!communicator::receiveFile($socket, $data['payload']['clientFile'], true, $data['payload']['overwrite'])){
                    return ["success"=>false, "error"=>"Failed to download file"];
                }
            }
        }

        $result = communicator::receiveData($socket, true);
        if(!is_array($result)){
            return ["success"=>false, "error"=>"Error receiving data"];
        }

        if(!isset($result['success'])){
            return ["success"=>false, "error"=>"Received incomplete data"];
        }

        return $result;
    }
}