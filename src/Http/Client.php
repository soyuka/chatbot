<?php
declare(strict_types=1);

namespace App\Http;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class Client
{
    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $client;
    
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }
    
    public function postMessage(string $service, string $message)
    {
        
        $response = $this->client->request('POST', 'http://0.0.0.0/' . $service, [
            'json' => ['message' => $message],
        ]);
        
        return $response;
    }
}
