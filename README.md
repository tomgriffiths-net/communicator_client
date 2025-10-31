# communicator_client
This is a package for PHP-CLI.

# Functions
- **runcommand(string $command, string $ip="127.0.0.1", int $port=8080):bool**: Tells communicator server to run a cli command, returns true if it was able to contact communicator server or false on failure.
- **runfunction(string $function, string $ip="127.0.0.1", int $port=8080):mixed**: Tells communicator server to run a function string, returns the return of the function on success or false on failure.