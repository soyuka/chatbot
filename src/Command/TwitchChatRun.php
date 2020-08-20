<?php
declare(strict_types=1);

namespace App\Command;

use App\Http\Server;
use App\Mercure\Topic;
use App\Twitch\Client;
use Psr\Http\Message\ServerRequestInterface;
use React\ChildProcess\Process;
use React\EventLoop\Factory as EventLoopFactory;
use React\Http\Message\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
use function Symfony\Component\String\u;

class TwitchChatRun extends Command
{
    protected static $defaultName = 'app:twitch:run';
    /**
     * @var Client
     */
    private Client $client;
    /**
     * @var Publisher
     */
    private Publisher $publisher;
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;
    private string $bootstrapPath;
    private array $commands;
    
    public function __construct(
        Client $client,
        Publisher $publisher,
        SerializerInterface $serializer,
        string $bootstrapPath,
        array $commands
    ) {
        
        $this->client = $client;
        $this->publisher = $publisher;
        $this->serializer = $serializer;
        $this->bootstrapPath = $bootstrapPath;
        parent::__construct();
        $this->commands = $commands;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consoleBin = getcwd() . '/bin/console';
        $io = new SymfonyStyle($input, $output);
        $loop = EventLoopFactory::create();
        foreach ($this->commands as $command) {
            $process = new Process($consoleBin . " " . $command . ' -vvv');
            $process->start($loop);
            
        }
        $clientSocket = $this->client->connect($loop);
        $server = new Server('tcp', '0.0.0.0', 80);
        $server->run($loop, function(ServerRequestInterface $request) use ($io) {
            $io->writeln($request->getUri()->getPath());
            $controller = 'App\\Drift\\Controller\\' . (string)u($request->getUri()->getPath())->slice(1)->camel()->title() . '\\' . u($request->getMethod())->lower()->camel()->title() . "Command";
            if (!class_exists($controller)) {
                return new Response(404);
            }
            
            return (new $controller())($this->client, $request);
        });
        $clientSocket->on('data', [$this->client, 'parse']);
        $clientSocket->on('message', function($data) use ($io) {
            $message = json_decode($data, true);
            $channel = $message['channel'][0] === '#' ? substr($message['channel'],
                1) : $message['channel']; // remove #
            $topics = [Topic::create(['<channel>' => $channel])];
            if ((bool)$message['isCommand']) {
                $topics[] = Topic::create(['<channel>' => $channel, '<command>' => $message['command']]);
            }
            $io->success('send message ' . json_encode($message));
            $this->publisher->__invoke(new Update($topics, $data));
            
        });
        $io->success('server is up !!!');
        $loop->run();
        
    }
}
