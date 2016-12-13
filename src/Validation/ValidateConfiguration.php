<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 20/06/2016
 * Time: 22:12
 */

namespace Warlof\Seat\Slackbot\Validation;

use App\Http\Requests\Request;

class ValidateConfiguration extends Request
{
    public function rules()
    {
        return [
            //'slack-configuration-client' => 'required|string',
            //'slack-configuration-secret' => 'required|string'
            'slack-configuration-token' => 'required|string'
        ];
    }
}