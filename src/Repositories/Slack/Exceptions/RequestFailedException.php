<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 22:59
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Exceptions;


use Exception;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackResponse;

class RequestFailedException extends Exception {

    private $response;

    private $exception;

    public function __construct(Exception $exception, SlackResponse $response) {

        $this->response = $response;
        $this->exception = $exception;

        parent::__construct($this->getError(), $this->getResponse()->getErrorCode(), $exception->getPrevious());
    }

    public function getError()
    {
        return $this->getResponse()->error();
    }

    public function getResponse() : SlackResponse
    {
        return $this->response;
    }

    public function getException() : Exception
    {
        return $this->exception;
    }

}