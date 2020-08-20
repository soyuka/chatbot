<?php
declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;

class Server
{
    public string $domain;
    
    public function __construct(
        string $httpSchema,
        string $host,
        int $port = 80
    ) {
        $this->domain = sprintf('%s://%s:%d', $httpSchema, $host, $port);
        
    }
    
    public function run(LoopInterface $loop, callable $func)
    {
      
        $server = new \React\Http\Server($loop, $func);
        $server->listen(new \React\Socket\Server($this->domain, $loop));
        
    }
}
