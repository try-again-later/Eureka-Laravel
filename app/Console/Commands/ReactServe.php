<?php

namespace App\Console\Commands;

use App\Exceptions\Handler;
use App\Exceptions\Http\ReactRequestException;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response as ReactResponse;
use React\Socket\SocketServer;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\Console\Input\InputOption;

class ReactServe extends Command
{
    protected $signature = 'app:react-serve {host=127.0.0.1} {port=8000}';

    protected $description = 'Serve the Laravel project using ReactPHP';

    public function handle(): void
    {
        $host = $this->argument('host');
        $port = $this->argument('port');

        $psrHttpFactory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory(
            serverRequestFactory: $psrHttpFactory,
            streamFactory: $psrHttpFactory,
            uploadedFileFactory: $psrHttpFactory,
            responseFactory: $psrHttpFactory,
        );

        /** @var Kernel $kernel */
        $kernel = app()->make(Kernel::class);


        $server = new HttpServer(function (ServerRequestInterface $psrRequest) use ($kernel, $psrHttpFactory) {
            $request = null;

            try {
                $httpFoundationFactory = new HttpFoundationFactory();
                $request = Request::createFromBase($httpFoundationFactory->createRequest($psrRequest));
                $response = $kernel->handle($request);
                $kernel->terminate($request, $response);

                return $psrHttpFactory->createResponse($response);
            } catch (Exception $exception) {
                throw new ReactRequestException($exception, $request);
            }
        });
        $server->on('error', function (Exception $exception) use ($psrHttpFactory) {
            $handler = resolve(Handler::class);
            $handler->report($exception);

            if ($exception instanceof ReactRequestException && $exception->hasRequest()) {
                return $psrHttpFactory->createResponse($handler->render($exception->getRequest(), $exception));
            } else {
                return new ReactResponse(500);
            }
        });

        $socket = new SocketServer("$host:$port");
        $server->listen($socket);
        $this->info("Server started at $host:$port.");
    }

    protected function getArguments(): array
    {
        return array(
            ['host', 'h', InputOption::VALUE_OPTIONAL, 'The host address to serve the application on.', '127.0.0.1'],
            ['port', 'p', InputOption::VALUE_OPTIONAL, 'The port to serve the application on.', 8000],
        );
    }
}
