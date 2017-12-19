<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 22:39
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Containers\Traits;


use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException;

trait ConstructsContainers {

    /**
     * ConstructsContainers constructor.
     *
     * @param array|null $data
     *
     * @throws InvalidContainerDataException
     */
    public function __construct(array $data = null) {

        if (!is_null($data)) {

            foreach ($data as $key => $value) {

                if (!array_key_exists($key, $this->data))
                    throw new InvalidContainerDataException('Key ' . $key . ' is not valid for this container');

                $this->$key = $value;

            }

        }

    }

}
