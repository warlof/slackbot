USE seat_test;

INSERT INTO `users` (`id`, `name`, `eve_id`, `email`, `password`, `token`, `active`, `last_login`, `last_login_source`, `remember_token`, `created_at`, `updated_at`)
VALUES ('1', 'admin', NULL, 'loic67@hotmail.fr', '$2y$10$fjvF.gn2zr.gEaLUSvxS/uyYuuG.dD0uNX7EokRg9ZJI.W4sK2rqu', NULL, '1', '2016-08-08 11:12:23', '192.168.1.34', 'R7JLEM6h0Rr5SthIcjUHBWzWfD0BtmTSedFXalbJ9j3C0PKFgkaDjV0SmB1N', '2016-08-07 20:27:19', '2016-08-08 11:12:23');

INSERT INTO `users` (`id`, `name`, `eve_id`, `email`, `password`, `token`, `active`, `last_login`, `last_login_source`, `remember_token`, `created_at`, `updated_at`)
VALUES ('3', 'test', NULL, 'elfaus@hotmail.fr', '$2y$10$fjvF.gn2zr.gEaLUSvxS/uyYuuG.dD0uNX7EokRg9ZJI.W4sK2rqu', NULL, '1', '2016-08-08 11:12:23', '192.168.1.34', 'R7JLEM6h0Rr5SthIcjUHBWzWfD0BtmTSedFXalbJ9j3C0PKFgkaDjV0SmB1N', '2016-08-07 20:27:19', '2016-08-08 11:12:23');

INSERT INTO `users` (`id`, `name`, `eve_id`, `email`, `password`, `token`, `active`, `last_login`, `last_login_source`, `remember_token`, `created_at`, `updated_at`)
VALUES ('2', 'test', NULL, 'tmnf1gp@free.fr', '$2y$10$fjvF.gn2zr.gEaLUSvxS/uyYuuG.dD0uNX7EokRg9ZJI.W4sK2rqu', NULL, '1', '2016-08-08 11:12:23', '192.168.1.34', 'R7JLEM6h0Rr5SthIcjUHBWzWfD0BtmTSedFXalbJ9j3C0PKFgkaDjV0SmB1N', '2016-08-07 20:27:19', '2016-08-08 11:12:23');

INSERT INTO `eve_alliance_lists` (`allianceID`, `name`, `shortName`, `executorCorpID`, `memberCount`, `startDate`, `created_at`, `updated_at`)
VALUES ('99000006', 'Everto Rex Regis', '666', '1983708877', '3', '2010-11-04 13:11:00', '2016-06-20 01:00:49', '2016-06-20 01:00:49');

INSERT INTO `roles` (`id`, `title`)
VALUES ('1', 'Superuser');

INSERT INTO `slack_users` (`user_id`, `slack_id`, `invited`, `created_at`, `updated_at`)
VALUES ('1', 'U1Z9LT9NM', '1', '2016-08-10 07:43:35', '2016-08-10 07:43:35');

INSERT INTO `slack_users` (`user_id`, `slack_id`, `invited`, `created_at`, `updated_at`)
VALUES ('3', '', '1', '2016-08-10 07:43:35', '2016-08-10 07:43:35');

INSERT INTO `slack_channels` (`id`, `name`, `is_group`, `is_general`, `created_at`, `updated_at`)
VALUES ('C1Z920QKC', 'random2', 0, 0, '2016-08-09 20:59:44', '2016-08-09 20:59:44');

INSERT INTO `slack_channel_corporations` (`corporation_id`, `channel_id`, `enable`, `created_at`, `updated_at`)
VALUES ('98413060', 'C1Z920QKC', '1', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

INSERT INTO `slack_channel_roles` (`role_id`, `channel_id`, `enable`, `created_at`, `updated_at`)
VALUES ('1', 'C1Z920QKC', '1', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

INSERT INTO `slack_channel_alliances` (`alliance_id`, `channel_id`, `enable`, `created_at`, `updated_at`)
VALUES ('99000006', 'C1Z920QKC', '1', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

INSERT INTO `slack_channel_public` (`channel_id`, `enable`, `created_at`, `updated_at`)
VALUES ('C1Z920QKC', '1', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

INSERT INTO `slack_channel_users` (`user_id`, `channel_id`, `enable`, `created_at`, `updated_at`)
VALUES ('1', 'C1Z920QKC', '1', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

INSERT INTO `slack_logs` (`id`, `event`, `message`)
VALUES ('100', 'kick', 'Some user has been kicked from following channels : ');

INSERT INTO `slack_logs` (`id`, `event`, `message`)
VALUES ('101', 'kick', 'Some user has been kicked from following channels : ');

INSERT INTO `slack_logs` (`id`, `event`, `message`)
VALUES ('102', 'invite', 'Some user has been invited to following channels : ');