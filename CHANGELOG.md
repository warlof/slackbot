# Version 2.1.8
* Add a 1 second cool down on Slack Api request according to their limitation
* Code refactorying

# Version 2.1.5
* Fix an issue which was preventing disabled account to be monitored by the bot (thanks @herpaderp)

# Version 2.1.4
* Fix an issue which was preventing a new instance to be deployed if this package was already present in `composer.json` file

# Version 2.1.3
* Fix an issue which was preventing to remove public filter
* Fix an issue which was returning the wrong channel name on public filter

# Version 2.1.2
* Add new user:email.read scope which has been added by Slack Team on November 9th.

**Warning**
> If you already had a working bot prior to this date, the update isn't mandatory and you have nothing to do.

> If you've setting up a new installation, between November 9th and February 18th, you **MUST** drop the existing 
application and create a new one. As soon as the new application has been created, you have to hit the red rubber Button 
on both `Client ID` and `Client Secret` fields into SeAT and fill them with the new credentials. Finally, you can hit the
`update` button which will redirect you to Slack Authentication as a fresh bot install.

# Version 2.1.0
* Add a filter for corporation title

# Version 2.0.1
* Fix few issues related to Slack implementation (you need to **restart** supervisor in order change are took in account)
* Improve cachability
* `Update Slack channels and groups` and `Update Slack users` commands in settings are now refreshing cache
* Slack log is no longer displaying an entry every time the bot is proceeding invitation
* Improve Slack User Mapping with the channels list where an user is in

# Version 2.0.0 announcement
This new version is compatible with SeAT 2.x. I choose to follow SeAT major version in order to make life easiest for maintainers.

Slack RTM has been replaced by Slack Event API which is easier to set than using a Daemon.

Due to this change, Slackbot is no longer processing **Team Invitation**. Team invitation is a non official endpoint which is only usable with test token, and Slack Event require official OAuth token.

**Warning**
> You have to create new credentials in `Slackbot Settings` (follow the link bellow credentials fields).

> Slackbot namespace has changed for `Warlof\Seat\Slackbot` and Slack credentials are now stored into official `Seat` setting table.

> Supervisor configuration related to Slackbot Daemon is non longer required

Redis is now used in order to reduce call amount through Slack REST API.

# Version 0.7.5
Handle `Team Invitation` exception in logs in order to avoid to barely spam SeAT stats with unrelevant exception.
Introduce a new event kind called "sync" into which those exception are published.

# Version 0.7.4
Add a constraint into `slack:users:update` command which exclude `slackbot` from valid users. It appears that `slackbot` is not considered as a bot by Slack API.
We're waiting for a ticket issue response from Slack Team related to this bug.

# Version 0.7.3
Add a local channels clear which will delete no longer valid channels to the command `slack:channels:update` if there is an issue with the daemon.

# Version 0.7.2
remove MPM channels from private channels list

# Version 0.7.0
fix log issues which was logging event even when they didn't happen.
rename slack command
exclude general channel from member list and add general flag to channels

**Warning**
> This update is an upgrade and need a migration. Run `php artisan migrate` command in order to update schemas.

> This update replace `slack:update:channels` and `slack:update:users` with `slack:channels:update`
and `slack:users:update` respectively.

> Due to latest modification, you need to run `slack:channels:update` command in order to update your `slack_channels`
flags.

Report any bugs on [github](https://github.com/warlof/slackbot/issues).

# Version 0.5.8
add test coverage for SlackApi Helper
fix Daemon issue related to user and channel creation

# Version 0.5.7
fix and refactor slack access creation

# Version 0.5.6
fix channels update refactoring issue which avoid new record to be saved

# Version 0.5.4
fix API issue for is_bot flag usage which is not available on deleted user
as a result, add deleted check

# Version 0.5.3
Fix issue for new user invitation

# Version 0.5.2
Implement channel_rename and group_rename event into Slack Daemon in order to keep channel table up to date
Add a way to rename channel when user run `slack:update:channels` and the channel already exist in SeAT

# Version 0.5.1
Fix an issue which was running `slack:update:channels` when people clic on "Update Slack users" from
Slackbot Settings UI.

# Version 0.5.0
Replace `jclg/php-slack-bot` package which avoid to use stable flag and cause some issue,
with `ratchet/pawl` and a homemade RTM daemon.

# Version 0.4.3
Fix a query issue introduced with version 0.4.0 and public filter

# Version 0.4.2
 Add a log system which store event history (invite, kick and mail issue)
 Remove unset mail exception from fail job

# Version 0.4.1
Fix manual user sync with job slack:update:users (set invited flag to true)

# Version 0.4.0
Add a way to create public filter (grant everybody to be in a channel)
Remove eveseat/seat package from dependencies

# Version 0.3.5
Fix 0.3.4 typo issue

# Version 0.3.4
Update Slack API and add a control to member function is order to check that a channel is not a MP channel
Fix javascript on setting view

# Version 0.3.3
Version fix

# Version 0.3.2

Fix an issue on slack:update:users command which avoid slack user refresh.

# Version 0.3.1

Due to a Slack API issue in OAuth mechanism which is used by this extension in order to invite and kick user,
the OAuth credentials has been temporarily disabled waiting an answer from Slack support.

In order to provide you a way to keep using the bot, the old connection method has been restored (using testing token).

You need to update the token using the test token and reload supervisor.

Sincere regrets for this perturbation.

# Version 0.3.0

In order to keep people inform, the release 0.3.0 provides a way to get live changelog.

This new feature will be used in order to describe last change in the plugin and will be a way to warn people
about new release.
