<?php

namespace App\Command;

use App\Twitch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

final class TwitchInterceptor extends Command
{
    private $client;
    private $publisher;
    private $serializer;
    
    public function __construct(Client $client, Publisher $publisher, SerializerInterface $serializer)
    {
        $this->client = $client;
        $this->publisher = $publisher;
        $this->serializer = $serializer;
        parent::__construct();
    }
    
    protected static $defaultName = 'app:twitch-interceptor';
    
    protected function configure()
    {
        $this
            ->setDescription('Listen to twitch IRC and publish updates on a Mercure hub.');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $stream = $this->client->connect();
        $this->client->sendMessage('Coucou je suis le bot');
        $stream->on('data', [$this->client, 'parse']);
        $stream->on('message', function($data) {
            $message = json_decode($data, true);
            $channel = substr($message['channel'], 1); // remove #
            $topics = [sprintf('https://twitch.tv/%s', $channel)];

            if ($message['isCommand']) {
                $topics[] = sprintf('https://twitch.tv/%s/command/%s', $channel, $message['command']);
            }
            $this->publisher->__invoke(new Update($topics, $data));
            
        });
        $this->client->run();
        
        return Command::SUCCESS;
    }
}
