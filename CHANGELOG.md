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