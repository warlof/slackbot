<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Seat\Slackbot\Commands\Corp;


use Illuminate\Console\Command;
use Seat\Slackbot\Bot\SlackReceptionist;

class SlackInvite extends Command
{
    protected $signature = 'slack:invite';

    protected $description = 'Auto invite member based on white list/slack relation';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        (new SlackReceptionist)->call();

        return;
    }
}
