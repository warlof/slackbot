<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 07/08/2016
 * Time: 09:00
 */

namespace Warlof\Seat\Slackbot\Http\Controllers;

use Illuminate\Http\Request;
use Seat\Web\Http\Controllers\Controller;

class EventController extends Controller
{
    public function callback(Request $request)
    {
        logger()->debug('Slack::callback', ['token' => $request->input('token')]);

        if ($request->input('token') == null || $request->input('type') == null ||
            !in_array($request->input('type'),['url_verification', 'event_callback'])) {

            logger()->error('Slack::callback missing either token or type, or the sent type is not supported.', [
                'token' => $request->input('token'),
                'type' => $request->input('type')
            ]);

            return response()->json(['message' => 'token field is required or message type is not supported.'], 400);
        }

        // take back our Slack oauth token
        if (setting('warlof.slackbot.credentials.verification_token', true) == null) {
            logger()->warning('Slack::callback receive a request to event endpoint but there is no OAuth configured or verification_token is missing.');

            return response()->json(['message' => 'oauth has not been set on this server.'], 403);
        }

        // compare our token to the token sent by Slack
        // if it don't match, inform Slack with 401 Unauthorized header
        if ($request->input('token') != setting('warlof.slackbot.credentials.verification_token', true)) {
            return response()->json([
                'token' => $request->input('token'),
                'type' => 'url_verification',
                'error' => 'You send me a wrong token.'], 401);
        }

        switch ($request->input('type'))
        {
            case 'url_verification':
                // since we're using url_verification, challenge field is mandatory
                if ($request->input('challenge') == null) {
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

                $this->eventHandler($request->input('event'));
                return response()->json(null, 200);
        }

        return response()->json(['message' => 'Unsupported event type'], 501);
    }

    private function eventHandler($event)
    {
        logger()->debug('Slack::eventHandler', $event);

        switch ($event['type']) {
            case 'channel_created':
                EventChannelController::postChannelCreated($event['channel']);
                break;
            case 'channel_deleted':
                EventChannelController::postChannelDeleted($event['channel']);
                break;
            case 'channel_archive':
                EventChannelController::postChannelArchive($event['channel']);
                break;
            case 'channel_unarchive':
                EventChannelController::postChannelUnarchive($event['channel']);
        }

    }
}
