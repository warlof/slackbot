<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 07/08/2016
 * Time: 09:00
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services;

use Illuminate\Http\Request;
use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\ConversationHandler;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\GroupHandler;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\UserHandler;

class EventController extends Controller
{
    use ConversationHandler, UserHandler;

    public function callback(Request $request)
    {
        if ($request->input('token') == null || $request->input('type') == null ||
            !in_array($request->input('type'), ['url_verification', 'event_callback'])) {

            logger()->error('Slack::callback missing either token or type, or the sent type is not supported.', [
                'token' => $request->input('token'),
                'type' => $request->input('type')
            ]);

            return response()->json(['error' => 'token field is required or message type is not supported.'], 400);
        }

        // take back our Slack oauth token
        if (setting('warlof.slackbot.credentials.verification_token', true) == null) {
            logger()->warning('Slack::callback receive a request to event endpoint but there is no OAuth configured ' .
                'or verification_token is missing.');

            return response()->json(['error' => 'oauth has not been set on this server.'], 500);
        }

        // compare our token to the token sent by Slack
        // if it don't match, inform Slack with 401 Unauthorized header
        if ($request->input('token') != setting('warlof.slackbot.credentials.verification_token', true)) {
            return response()->json([
                'token' => $request->input('token'),
                'type' => 'url_verification',
                'error' => 'You send me a wrong token.'], 403);
        }

        switch ($request->input('type')) {
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

                return $this->eventHandler($request->input('event'));
        }

        return response()->json(['error' => 'Unsupported event type'], 501);
    }

    /**
     * @param $event array A Slack Json event object
     */
    private function eventHandler(array $event)
    {
        switch ($event['type']) {
            //
            // conversation events
            //
            case 'channel_created':
            case 'group_created':
                $this->createChannel($event['channel']);
                break;
            case 'channel_deleted':
            case 'group_deleted':
                $this->deleteChannel($event['channel']);
                break;
            case 'channel_archive':
            case 'group_archive':
                $this->archiveChannel($event['channel']);
                break;
            case 'channel_unarchive':
            case 'group_unarchive':
                $this->unarchiveChannel($event['channel']);
                break;
            case 'channel_rename':
            case 'group_rename':
                $this->renameChannel($event['channel']);
                break;
            //
            // user events
            //
            case 'user_change':
                $this->userChange($event['user']);
                break;
            case 'team_join':
                $this->joinTeam($event['user']);
                break;
            case 'message':
                $expectedSubEvent = [
                    'channel_join',
                    'channel_leave',
                    'group_join',
                    'group_unarchive',
                    'group_leave',
                    'group_archive',
                ];

                if (!isset($event['subtype'])) {
                    return response()->json([
                        'ok' => true,
                        'msg' => sprintf('Expected %s sub-events for message event', implode(', ', $expectedSubEvent))
                    ], 202);
                }

                switch ($event['subtype']) {
                    case 'channel_join':
                        $this->joinChannel($event);
                        break;
                    case 'channel_leave':
                        $this->leaveChannel($event);
                        break;
                    case 'group_join':
                    case 'group_unarchive':
                        $this->joinGroup($event);
                        break;
                    case 'group_leave':
                    case 'group_archive':
                        $this->leaveGroup($event);
                        break;
                }

                break;
            default:
                return response()->json([
                    'ok' => true,
                    'msg' => 'Unhandled event'
                ], 202);
        }

        return response()->json(['ok' => true], 200);
    }
}
