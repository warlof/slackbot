<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Seat\Slackbot\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Eve\AllianceList;
use Seat\Services\Settings\Seat;
use Seat\Slackbot\Models\SlackChannel;
use Seat\Slackbot\Models\SlackChannelPublic;
use Seat\Slackbot\Models\SlackChannelUser;
use Seat\Slackbot\Models\SlackChannelRole;
use Seat\Slackbot\Models\SlackChannelCorporation;
use Seat\Slackbot\Models\SlackChannelAlliance;
use Seat\Slackbot\Models\SlackLog;
use Seat\Slackbot\Models\SlackOAuth;
use Seat\Slackbot\Validation\AddRelation;
use Seat\Slackbot\Validation\ValidateConfiguration;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\User;

class SlackbotController extends Controller
{

    public function getRelations()
    {
        $channelPublic = SlackChannelPublic::all();
        $channelUsers = SlackChannelUser::all();
        $channelRoles = SlackChannelRole::all();
        $channelCorporations = SlackChannelCorporation::all();
        $channelAlliances = SlackChannelAlliance::all();
        
        $users = User::all();
        $roles = Role::all();
        $corporations = CorporationSheet::all();
        $alliances = AllianceList::all();
        $channels = SlackChannel::all();

        return view('slackbot::list',
            compact('channelPublic', 'channelUsers', 'channelRoles', 'channelCorporations', 'channelAlliances',
                'users', 'roles', 'corporations', 'alliances', 'channels'));
    }

    public function getConfiguration()
    {
        $token = Seat::get('slack_token');
        $oauth = SlackOAuth::first();

        $parser = new \Parsedown();
        $changelog = $parser->parse($this->getChangelog());
        
        return view('slackbot::configuration', compact('oauth', 'token', 'changelog'));
    }
    
    public function getLogs()
    {
        $logs = SlackLog::all();

        return view('slackbot::logs', compact('logs'));
    }

    public function postRelation(AddRelation $request)
    {
        // use a single post route in order to create any kind of relation
        // value are user, role, corporation or alliance
        switch ($request->input('slack-type')) {
            case 'public':

                $channelId = $request->input('slack-channel-id');

                if (SlackChannelPublic::find($channelId) == null) {
                    $relation = new SlackChannelPublic();
                    $relation->channel_id = $channelId;
                    $relation->save();

                    return redirect()->back()
                        ->with('success', 'New public slack relation has been created');
                }

                return redirect()->back()
                    ->with('error', 'This relation already exists');
            case 'user':

                $userId = $request->input('slack-user-id');
                $channelId = $request->input('slack-channel-id');

                if (SlackChannelUser::find([$userId, $channelId]) == null) {
                    $relation = new SlackChannelUser();
                    $relation->user_id = $userId;
                    $relation->channel_id = $channelId;
                    $relation->save();

                    return redirect()->back()
                        ->with('success', 'New slack user relation has been created');
                }

                return redirect()->back()
                    ->with('error', 'This relation already exists');
            case 'role':

                $roleId = $request->input('slack-role-id');
                $channelId = $request->input('slack-channel-id');

                if (SlackChannelRole::find([$roleId, $channelId]) == null) {
                    $relation = new SlackChannelRole();
                    $relation->role_id = $roleId;
                    $relation->channel_id = $channelId;
                    $relation->save();

                    return redirect()->back()
                        ->with('success', 'New slack role relation has been created');
                }

                return redirect()->back()
                    ->with('error', 'This relation already exists');
            case 'corporation':

                $corporationId = $request->input('slack-corporation-id');
                $channelId = $request->input('slack-channel-id');

                if (SlackChannelCorporation::find([$corporationId, $channelId]) == null) {
                    $relation = new SlackChannelCorporation();
                    $relation->corporation_id = $corporationId;
                    $relation->channel_id = $channelId;
                    $relation->save();

                    return redirect()->back()
                        ->with('success', 'New slack corporation relation has been created');
                }

                return redirect()->back()
                    ->with('error', 'This relation already exists');
            case 'alliance':
                
                $allianceId = $request->input('slack-alliance-id');
                $channelId = $request->input('slack-channel-id');
                
                if (SlackChannelAlliance::find([$allianceId, $channelId]) == null) {
                    $relation = new SlackChannelAlliance();
                    $relation->alliance_id = $allianceId;
                    $relation->channel_id = $channelId;
                    $relation->save();

                    return redirect()->back()
                        ->with('success', 'New slack alliance relation has been created');
                }

                return redirect()->back()
                    ->with('error', 'This relation already exists');
            default:
                return redirect()->back()
                    ->with('error', 'Unknown relation type');
        }
    }
    
    public function postConfiguration(ValidateConfiguration $request)
    {
        /*
        $slackOauth = SlackOAuth::find($request->input('slack-configuration-client'));

        if ($slackOauth != null)
            $slackOauth->delete();

        $slackOauth = new SlackOAuth();
        $slackOauth->client_id = $request->input('slack-configuration-client');
        $slackOauth->client_secret = $request->input('slack-configuration-secret');
        $slackOauth->state = time();
        $slackOauth->save();

        return redirect($this->oAuthAuthorization($request->input('slack-configuration-client'), $slackOauth->state));
        */
        Seat::set('slack_token', $request->input('slack-configuration-token'));
        return redirect()->back()
            ->with('success', 'The Slack test token has been updated');
    }

    public function getRemovePublic($channelId)
    {
        $channelPublic = SlackChannelPublic::where('channel_id', $channelId);

        if ($channelPublic != null) {
            $channelPublic->delete();
            return redirect()->back()
                ->with('success', 'The public slack relation has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the public Slack relation.');
    }

    public function getRemoveUser($userId, $channelId)
    {
        $channelUser = SlackChannelUser::where('user_id', $userId)
            ->where('channel_id', $channelId);

        if ($channelUser != null) {
            $channelUser->delete();
            return redirect()->back()
                ->with('success', 'The slack relation for the user has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Slack relation for the user.');
    }

    public function getRemoveRole($roleId, $channelId)
    {
        $channelRole = SlackChannelRole::where('role_id', $roleId)
            ->where('channel_id', $channelId);

        if ($channelRole != null) {
            $channelRole->delete();
            return redirect()->back()
                ->with('success', 'The slack relation for the role has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Slack relation for the role.');
    }

    public function getRemoveCorporation($corporationId, $channelId)
    {
        $channelCorporation = SlackChannelCorporation::where('corporation_id', $corporationId)
            ->where('channel_id', $channelId);

        if ($channelCorporation != null) {
            $channelCorporation->delete();
            return redirect()->back()
                ->with('success', 'The slack relation for the corporation has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Slack relation for the corporation.');
    }

    public function getRemoveAlliance($allianceId, $channelId)
    {
        $channelAlliance = SlackChannelAlliance::where('alliance_id', $allianceId)
            ->where('channel_id', $channelId);

        if ($channelAlliance != null) {
            $channelAlliance->delete();
            return redirect()->back()
                ->with('success', 'The slack relation for the alliance has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Slack relation for the alliance.');
    }

    public function getSubmitJob($commandName)
    {
        $accepted_commands = [
            'slack:update:channels',
            'slack:update:users'
        ];
        
        if (!in_array($commandName, $accepted_commands))
            abort(401);

        Artisan::call($commandName);

        return redirect()->back()
            ->with('success', 'The command has been run.');
    }
    
    private function oAuthAuthorization($clientId, $state)
    {
        $baseUri = 'https://slack.com/oauth/authorize?';
        $scope = 'channels:read channels:write groups:read groups:write users:read';

        return $baseUri . http_build_query([
                'client_id' => $clientId,
                'scope' => $scope,
                'state' => $state
            ]);
    }

    public function getOAuthToken(Request $request)
    {
        // get slack_oauth table and check that state match with $state
        $slackOAuth = SlackOAuth::whereNotNull('state')
            ->first();

        if ($slackOAuth != null) {

            if ($slackOAuth->state != $request->input('state')) {
                $slackOAuth->delete();

                redirect()->back()
                    ->with('error', 'An error occurred while getting back the token.');
            }

            $parameters = [
                'client_id' => $slackOAuth->client_id,
                'client_secret' => $slackOAuth->client_secret,
                'code' => $request->input('code')
            ];

            // prepare curl request using passed parameters and endpoint
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://slack.com/api/oauth.access');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = json_decode(curl_exec($curl), true);

            if ($result == null) {
                throw new \Exception("An error occurred while asking the token to Slack API\r\n" . curl_error($curl));
            }

            if ($result['ok'] == false) {
                throw new \Exception("An error occurred while getting back the token from Slack API\r\n" . $result['error']);
            }

            Seat::set('slack_token', $result['access_token']);

            $slackOAuth->state = null;
            $slackOAuth->save();

            return redirect()->route('slackbot.configuration')
                ->with('success', 'The bot credentials has been set.');
        }

        return redirect()->route('slackbot.configuration')
            ->with('error', 'The process has been aborted in order to prevent any security issue.');
    }

    private function getChangelog()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://raw.githubusercontent.com/warlof/slackbot/master/CHANGELOG.md");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        return curl_exec($curl);
    }
}
