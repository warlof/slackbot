<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 16/12/2016
 * Time: 22:45
 */

namespace Warlof\Seat\Slackbot\Http\Validation;


use Illuminate\Foundation\Http\FormRequest;

class ValidateOAuth extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'slack-configuration-client' => 'required|string',
            'slack-configuration-secret' => 'required|string',
            'slack-configuration-verification' => 'string'
        ];
    }
}
