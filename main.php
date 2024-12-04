<?php
class communicator_client{
    public static function runfunction(string $function):mixed{
        $result = self::run('127.0.0.1', 8080, array("type"=>"function_string","payload"=>$function));
        if($result["success"]){
            return $result["result"];
        }
        return false;
    }
    public static function runcommand(string $command):bool{
        $result = self::run('127.0.0.1', 8080, array("type"=>"command","payload"=>$command));
        return $result["success"];
    }
    public static function run(string $ip, int $port, array $data):array{
        $socket = communicator::connect($ip,$port,false,$socketError,$socketErrorString);
        if($socket !== false){
            return self::execute($socket,$data);
        }
        return array("success"=>false,"error"=>"Unable to connect to " . $ip . ":" . $port);
    }
    private static function execute($socket, array $data):array{
        $return = array("success"=>false);

        if(!isset($data['type'])){
            $return["error"] = "Type not set";
            goto end;
        }
        if(!in_array($data['type'],array("function_string","command","stop"))){
            $return["error"] = "Type not recognised";
            goto end;
        }

        if(!isset($data['payload'])){
            $return["error"] = "Payload not set";
            goto end;
        }

        $data['name'] = communicator::getName();
        $data['password'] = communicator::getPasswordEncoded();

        $data = base64_encode(json_encode($data));

        if(!communicator::send($socket,$data)){
            $return["error"] = "Error sending data";
            goto end;
        }

        $result = communicator::receive($socket);
        if($result === false){
            $return["error"] = "Error receiving data";
            goto end;
        }

        $result = json_decode(base64_decode($result),true);
        if($result === null){
            $return["error"] = "Empty response";
            goto end;
        }

        $return["success"] = true;
        $return["result"] = $result;

        end:
        communicator::close($socket);
        return $return;
    }
}