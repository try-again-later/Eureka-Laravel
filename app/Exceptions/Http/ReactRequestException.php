<?php

declare(strict_types=1);

namespace App\Exceptions\Http;

use Exception;
use Illuminate\Http\Request as LaravelRequest;

class ReactRequestException extends Exception
{
    protected ?LaravelRequest $request = null;

    public function __construct(Exception $e, ?LaravelRequest $request)
    {
        parent::__construct($e->getMessage(), $e->getCode(), $e->getPrevious());
        $this->request = $request;
    }

    public function getRequest(): ?LaravelRequest
    {
        return $this->request;
    }

    public function hasRequest(): bool
    {
        return $this->request !== null;
    }
}
