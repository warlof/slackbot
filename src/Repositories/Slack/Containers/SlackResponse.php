<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 21:35
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Containers;


use ArrayObject;
use Carbon\Carbon;
use stdClass;

class SlackResponse extends ArrayObject
{
    protected $expires_at;

    protected $response_code;

    protected $error_message;

    public function __construct(stdClass $data, string $expires, int $response_code)
    {
        $this->expires_at    = strlen($expires) > 2 ? $expires : 'now';
        $this->response_code = $response_code;

        // force cache on entries
        if (carbon($this->expires_at)->lte(carbon()->now(carbon($this->expires_at)->timezoneName)))
            $this->expires_at = carbon('now')->addHour();

        if (property_exists($data, 'error'))
            $this->error_message = $data->error;

        if (property_exists($data, 'errors'))
            $this->error_message = print_r($data->errors, true);

        if (property_exists($data, 'error_description'))
            $this->error_message .= $data->error_description;

        parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
    }

    public function expired() : bool
    {
        if ($this->expires()->lte(
            carbon()->now($this->expires()->timezoneName)
        ))
            return true;

        return false;
    }

    public function expires() : Carbon
    {
        return carbon($this->expires_at);
    }

    public function error()
    {
        return $this->error_message;
    }

    public function getErrorCode() : int
    {
        return $this->response_code;
    }

}
