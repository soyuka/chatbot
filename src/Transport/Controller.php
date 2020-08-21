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

namespace App\Transport;

use Psr\Http\Message\ServerRequestInterface;
use RingCentral\Psr7\Response;

final class Controller
{
    public function __invoke(ServerRequestInterface $request, ClientInterface $client): Response
    {
        $body = json_decode($request->getBody()->getContents(), true);
        $client->sendMessage($body['message']);

        return new Response(202);
    }
}
