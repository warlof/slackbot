<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 28/10/2017
 * Time: 18:25
 */

namespace Warlof\Seat\Slackbot\Http\Controllers;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Artisan;
use Seat\Web\Http\Controllers\Controller;

class SlackbotSettingsController extends Controller
{
    public function getConfiguration()
    {
        $changelog = $this->getChangelog();

        return view('slackbot::configuration', compact('changelog'));
    }

    public function getSubmitJob($commandName)
    {
        $acceptedCommands = [
            'slack:channels:update',
            'slack:users:update',
            'slack:logs:clear'
        ];

        if (!in_array($commandName, $acceptedCommands)) {
            abort(401);
        }

        Artisan::call($commandName);

        return redirect()->back()
            ->with('success', 'The command has been run.');
    }

    private function getChangelog() : string
    {
        try {
            $response = (new Client())
                ->request('GET', "https://raw.githubusercontent.com/warlof/slackbot/master/CHANGELOG.md");

            if ($response->getStatusCode() != 200) {
                return 'Error while fetching changelog';
            }

            $parser = new \Parsedown();
            return $parser->parse($response->getBody());
        } catch (RequestException $e) {
            return 'Error while fetching changelog';
        }
    }
}
