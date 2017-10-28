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

    public function callback(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|string',
            'type' => 'required|in:url_verification,event_callback',
        ]);

        // take back our Slack oauth token
        if (setting('warlof.slackbot.credentials.verification_token', true) == null) {
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
     * Business router which is handling Slack event
     *
     * @param array $event A Slack Json event object
     * @return JsonResponse
     */
    private function eventHandler(array $event) : JsonResponse
    {
        switch ($event['type']) {
            //
            // conversation events
            //
            case 'channel_created':
            case 'group_created':
            case 'channel_deleted':
            case 'group_deleted':
            case 'channel_archive':
            case 'group_archive':
            case 'channel_unarchive':
            case 'group_unarchive':
            case 'channel_rename':
            case 'group_rename':
                $this->eventConversationHandler($event);
                break;
            //
            // user events
            //
            case 'user_change':
            case 'team_join':
                $this->eventUserHandler($event);
                break;
            case 'message':
                return $this->eventMessageHandler($event);
            default:
                return response()->json([
                    'ok' => true,
                    'msg' => 'Unhandled event'
                ], 202);
        }

        return response()->json(['ok' => true], 200);
    }

    private function eventConversationHandler(array $event) : void
    {
        switch ($event['type']) {
            //
            // conversation events
            //
            case 'channel_created':
            case 'group_created':
                $this->createConversation($event['channel']);
                break;
            case 'channel_deleted':
            case 'group_deleted':
                $this->deleteConversation($event['channel']);
                break;
            case 'channel_archive':
            case 'group_archive':
                $this->archiveConversation($event['channel']);
                break;
            case 'channel_unarchive':
            case 'group_unarchive':
                $this->unarchiveConversation($event['channel']);
                break;
            case 'channel_rename':
            case 'group_rename':
                $this->renameConversation($event['channel']);
                break;
        }
    }

    private function eventUserHandler(array $event) : void
    {
        switch ($event['type']) {
            case 'user_change':
                $this->userChange($event['user']);
                break;
            case 'team_join':
                $this->joinTeam($event['user']);
                break;
        }
    }

    /**
     * Business router which is handling Slack message event
     *
     * @param array $event A Slack Json event object
     * @return JsonResponse
     */
    private function eventMessageHandler(array $event) : JsonResponse
    {
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
                'msg' => sprintf('Expected %s subtype for message event', implode(', ', $expectedSubEvent))
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
            default:
                return response()->json([
                    'ok' => true,
                    'msg' => sprintf('Expected %s subtype for message event', implode(', ', $expectedSubEvent))
                ], 202);
        }

        return response()->json(['ok' => true], 200);
    }
}
