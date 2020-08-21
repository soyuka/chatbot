<?php

/*
 * This file is part of the Bizmuth Bot project
 *
 * (c) Antoine Bluchet <antoine@bluchet.fr>
 * (c) Lemay Marc <flugv1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Command;

use App\Http\Server as HttpServer;
use App\Transport\Controller as TransportController;
use App\Twitch\Client as TwitchClient;
use Psr\Http\Message\ServerRequestInterface;
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

class Server extends Command
{
    protected static $defaultName = 'app:server:run';
    private TwitchClient $twitchClient;
    private Publisher $publisher;
    private SerializerInterface $serializer;
    private HttpServer $httpServer;
    private TransportController $transportController;

    public function __construct(
        TwitchClient $twitchClient,
        Publisher $publisher,
        SerializerInterface $serializer,
        HttpServer $httpServer,
        TransportController $transportController
    ) {
        $this->twitchClient = $twitchClient;
        $this->publisher = $publisher;
        $this->serializer = $serializer;
        $this->httpServer = $httpServer;
        $this->transportController = $transportController;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $loop = EventLoopFactory::create();

        $this->httpServer->run($loop, function (ServerRequestInterface $request) use ($io) {
            if (false === strpos($request->getHeader('content-type')[0], 'application/json')) {
                return new Response(400);
            }

            $path = (string) u($request->getUri()->getPath())->lower();
            $parts = explode('/', $path);

            if ('transport' === $parts[0]) {
                try {
                    return $this->transportController->__invoke($request, $this->getClient($parts[1]));
                } catch (\Exception $e) {
                    $io->error($e->getMessage());

                    return new Response(500);
                }
            }

            return new Response(404);
        });

        $clientSocket = $this->twitchClient->connect($loop);
        $clientSocket->on('data', function ($data) {
            foreach ($this->twitchClient->parse($data) as $message) {
                $channel = substr($message->getChannel(), 1); // remove #
                $topics = [sprintf('https://twitch.tv/%s', $channel)];
                if ($message->isCommand()) {
                    $topics[] = sprintf('https://twitch.tv/%s/command/%s', $channel, $message->getCommand());
                }

                $this->publisher->__invoke(new Update($topics, $this->serializer->serialize($message, 'json')));
            }
        });

        $io->success('Server is up on '.$this->httpServer->getHttpHost());
        $loop->run();

        return Command::SUCCESS;
    }

    private function getClient(string $client)
    {
        switch ($client) {
            case 'twitch':
              return $this->twitchClient;
        }
    }
}
