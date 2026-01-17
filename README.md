# communicator_client
This is a package for PHP-CLI.

# Functions
- **runcommand(string $command, string $ip="127.0.0.1", int $port=8080):bool**: Tells communicator server to run a cli command, returns true if it was able to contact communicator server or false on failure.
- **runfunction(string $function, string $ip="127.0.0.1", int $port=8080):mixed**: Tells communicator server to run a function string, returns the return of the function on success or false on failure.
- **serverCalledMe():bool**: Returns true if communicator_server class is anywhere in the backtrace, false otherwise. This is usefull for making sure communicator_server doesnt end up calling itself.