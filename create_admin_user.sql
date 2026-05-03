-- Create a OpenClanCMS admin user when cs_users is empty.
-- Login after running this:
--   Username: admin
--   Password: Bl4f4s3L4711$12345$
--
-- Adjust the "cs_" table prefix if setup.php uses another prefix.

ALTER TABLE `cs_users`
  MODIFY `users_pwd` varchar(255) NOT NULL default '';

SET @admin_access_id := (
  SELECT `access_id`
  FROM `cs_access`
  WHERE `access_clansphere` = 5
  ORDER BY `access_id` DESC
  LIMIT 1
);

INSERT INTO `cs_users` (
  `access_id`,
  `users_nick`,
  `users_pwd`,
  `users_lang`,
  `users_email`,
  `users_emailregister`,
  `users_country`,
  `users_register`,
  `users_laston`,
  `users_timezone`,
  `users_dstime`,
  `users_newsletter`,
  `users_active`,
  `users_limit`,
  `users_regkey`,
  `users_picture`,
  `users_hidden`,
  `users_delete`
) VALUES (
  @admin_access_id,
  'admin',
  MD5('Bl4f4s3L4711$12345$'),
  'German',
  'admin@relaxedgamers.de',
  'admin@relaxedgamers.de',
  'de',
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP(),
  3600,
  0,
  0,
  1,
  20,
  '',
  '',
  'users_email',
  0
);

SELECT
  `users_id`,
  `users_nick`,
  `access_id`,
  `users_active`,
  `users_delete`,
  LENGTH(`users_pwd`) AS `password_hash_length`,
  `users_pwd`
FROM `cs_users`
WHERE `users_nick` = 'admin';
