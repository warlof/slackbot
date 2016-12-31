<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 20/06/2016
 * Time: 22:12
 */

namespace Warlof\Seat\Slackbot\Http\Validation;


use Illuminate\Foundation\Http\FormRequest;

class UserChannel extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'slack_id' => 'required|string'
        ];
    }
}