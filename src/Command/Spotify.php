<?php

namespace App\Command;

use App\Mercure\Consumer as MercureConsumer;
use App\Spotify\Client as SpotifyClient;
use App\Twitch\Client as TwitchClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Spotify extends Command
{
    private $mercureConsumer;
    private $twitchClient;
    private $spotifyClient;
    private string $twitchChannel;
    
    public function __construct(
        TwitchClient $twitchClient,
        MercureConsumer $mercureConsumer,
        SpotifyClient $spotifyClient,
        string $twitchChannel
    ) {
        $this->mercureConsumer = $mercureConsumer;
        $this->twitchClient = $twitchClient;
        $this->spotifyClient = $spotifyClient;
        parent::__construct();
        $this->twitchChannel = $twitchChannel;
    }
    
    protected static $defaultName = 'app:spotify';
    
    protected function configure()
    {
        $this
            ->setDescription('User\'s Currently Playing Track.')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $topics = [sprintf('https://twitch.tv/%s/command/music', $this->twitchChannel)];
        $this->twitchClient->connect();
        foreach ($this->mercureConsumer->__invoke($topics) as $message) {
            $this->twitchClient->sendMessage('Current track: ' . $this->spotifyClient->getCurrentTrack());
            $this->twitchClient->run(0.1);
        }
        
        return Command::SUCCESS;
    }
}
