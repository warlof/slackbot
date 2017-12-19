<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 07/08/2016
 * Time: 09:00
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\ConversationHandler;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\UserHandler;

class EventController extends Controller
{
    use ConversationHandler, UserHandler;

	/**
	 * @param Request $request
	 *
	 * @return JsonResponse
	 * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
	 */
    public function callback(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|string',
            'type' => 'required|in:url_verification,event_callback',
        ]);

        // take back our Slack oauth token
        if (is_null(setting('warlof.slackbot.credentials.verification_token', true))) {
            logger()->warning('Slack::callback receive a request to event endpoint but there is no OAuth configured ' .
                'or verification_token is missing.');

            return response()->json(['error' => 'oauth has not been set yet on this server.'], 501);
        }

        // compare our token to the token sent by Slack
        // if it doesn't match, inform Slack with 401 Unauthorized header
        if ($request->input('token') != setting('warlof.slackbot.credentials.verification_token', true)) {
            return response()->json([
                'token' => $request->input('token'),
                'type' => 'url_verification',
                'error' => 'You send me a wrong token.'], 401);
        }

        switch ($request->input('type')) {
            case 'url_verification':
                // since we're using url_verification, challenge field is mandatory
                if (is_null($request->input('challenge'))) {
                    return response()->json(null, 400);
                }

                // since token match, return the challenge token received from Slack
                // using a 200 success header
                return response()->json(
                    ['challenge' => $request->input('challenge')], 200);

            case 'event_callback':
                // since we're using event_callback, event field is mandatory
                if ($request->input('event') == null) {
                    return response()->json(null, 400);
                }

                return $this->eventHandler($request->input('event'));
        }

        return response()->json(['error' => 'Unsupported event type'], 501);
    }

    /**
     * Business router which is handling Slack event
     *
     * @param array $event A Slack Json event object
     *
     * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     *
     * @return JsonResponse
	 */
    private function eventHandler(array $event) : JsonResponse
    {
        // conversation events
        if (in_array($event['type'], $this->conversationEvents)) {
            $this->eventConversationHandler($event);
        }

        // user events
        if (in_array($event['type'], $this->userEvents)) {
            $this->eventUserHandler($event);
        }

        // message event
        if ($event['type'] == 'message') {
            return $this->eventMessageHandler();
        }

        return response()->json(['ok' => true, 'msg' => 'Unhandled event'], 202);
    }
}
