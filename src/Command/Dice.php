<?php

namespace App\Command;

use App\Http\Client as HttpClient;
use App\Mercure\Consumer as MercureConsumer;
use App\Message\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Dice extends Command
{
    private $mercureConsumer;
    private $httpClient;
    private string $twitchChannel;
    
    public function __construct(HttpClient $httpClient, MercureConsumer $mercureConsumer, string $twitchChannel)
    {
        $this->mercureConsumer = $mercureConsumer;
        $this->httpClient = $httpClient;
        parent::__construct();
        $this->twitchChannel = $twitchChannel;
    }
    
    protected static $defaultName = 'app:dice';
    
    protected function configure()
    {
        $this
            ->setDescription('Rolls a dice and answer to twitch when requested.');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $topics = [sprintf('https://twitch.tv/%s/command/dice', $this->twitchChannel)];
        /** @var Message $message */
        foreach ($this->mercureConsumer->__invoke($topics) as $message) {
            $dice = $message->getCommandArguments()[0] ?? 6;
            $rand = random_int(1, $dice);
            $message = sprintf('%s sent a %d dice resulting in a %d',
                '@' . $message->getNickname(),
                $dice,
                $rand
            );
            $this->httpClient->postMessage('twitch', $message);
            
        }
        
        return Command::SUCCESS;
    }
}
