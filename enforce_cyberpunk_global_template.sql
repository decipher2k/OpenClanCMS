-- Make Cyberpunk the global OpenClanCMS template/theme for all users.
-- Existing per-user template/theme selections are cleared so the global
-- admin setting is used for everyone.
--
-- Adjust the "cs_" table prefix if setup.php uses another prefix.

UPDATE `cs_options`
SET `options_value` = 'cyberpunk'
WHERE `options_mod` = 'clansphere'
  AND `options_name` IN ('def_tpl', 'def_theme');

UPDATE `cs_users`
SET `users_tpl` = '',
    `users_theme` = '';
