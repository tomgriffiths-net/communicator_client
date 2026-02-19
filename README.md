# communicator_client
This is a package for PHP-CLI.

# Functions
- **runcommand(string $command, string $ip="127.0.0.1", int $port=8080):bool**: Tells communicator server to run a cli command, returns true if it was able to contact communicator server or false on failure.
- **runfunction(string $function, string $ip="127.0.0.1", int $port=8080):mixed**: Tells communicator server to run a function string, returns the return of the function on success or false on failure.
- **uploadFile(string $serverFileName, string $localFileName, bool $overwrite, string $ip, int $port=8080):string|false**: Uploads a file to communicator server, returns the path the server stored it as on success or false on failure.
- **downloadFile(string $serverFileName, string $localFileName, bool $overwrite, string $ip, int $port=8080):string|false**: Downloads a file from communicator server, returns where communicator server got the file from on success or false on failure.
- **serverCalledMe():bool**: Returns true if communicator_server class is anywhere in the backtrace, false otherwise. This is usefull for making sure communicator_server doesnt end up calling itself.