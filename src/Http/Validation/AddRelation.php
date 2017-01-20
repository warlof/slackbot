<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 20/06/2016
 * Time: 22:12
 */

namespace Warlof\Seat\Slackbot\Http\Validation;


use Illuminate\Foundation\Http\FormRequest;

class AddRelation extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'slack-type' => 'required|string',
            'slack-user-id' => 'string',
            'slack-role-id' => 'string',
            'slack-corporation-id' => 'string',
            'slack-title-id' => 'string',
            'slack-alliance-id' => 'string',
            'slack-channel-id' => 'required|string',
            'slack-enabled' => 'boolean'
        ];
    }
}