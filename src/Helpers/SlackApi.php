<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 26/06/2016
 * Time: 10:58
 */

namespace Seat\Slackbot\Helpers;


use Seat\Services\Models\GlobalSetting;
use Seat\Slackbot\Exceptions\SlackApiException;
use Seat\Slackbot\Exceptions\SlackSettingException;

class SlackApi
{
    /**
     * Determine the base slack api uri
     */
    const SLACK_URI_PATTERN = "https://slack.com/api";

    /**
     * Return the Slack Api Token set in SeAT configuration
     * 
     * @return string
     * @throws SlackSettingException
     */
    public static function getSlackToken()
    {
        // load token and team uri from settings
        $setting = GlobalSetting::where('name', 'slack_token')->first();

        if ($setting == null)
            throw new SlackSettingException("missing slack_token in settings");

        return $setting->value;
    }

    /**
     * Make a post action to the Slack API
     * 
     * @param $endpoint Slack API method
     * @param array $parameters Slack API parameters (except token)
     * @return array An array from the Slack API response (json parsed)
     * @throws SlackApiException
     * @throws SlackSettingException
     */
    public static function post($endpoint, $parameters = [])
    {
        // add slack token to the post parameters
        $parameters['token'] = self::getSlackToken();

        // prepare curl request using passed parameters and endpoint
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::SLACK_URI_PATTERN . $endpoint);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = json_decode(curl_exec($curl), true);

        if ($result == null) {
            throw new SlackApiException("An error occurred while calling the Slack API\r\n" . curl_error($curl));
        }

        return $result;
    }
}