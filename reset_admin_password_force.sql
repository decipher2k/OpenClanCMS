-- Force-reset the first OpenClanCMS user to a working admin login.
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

SET @admin_user_id := (
  SELECT `users_id`
  FROM `cs_users`
  ORDER BY `users_id` ASC
  LIMIT 1
);

UPDATE `cs_users`
SET
  `access_id` = @admin_access_id,
  `users_nick` = 'admin',
  `users_pwd` = MD5('Bl4f4s3L4711$12345$'),
  `users_active` = 1,
  `users_delete` = 0,
  `users_regkey` = ''
WHERE `users_id` = @admin_user_id;

SELECT
  `users_id`,
  `users_nick`,
  `access_id`,
  `users_active`,
  `users_delete`,
  LENGTH(`users_pwd`) AS `password_hash_length`,
  `users_pwd`
FROM `cs_users`
WHERE `users_id` = @admin_user_id;
