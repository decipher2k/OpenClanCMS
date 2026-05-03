-- Reset OpenClanCMS admin password.
-- Password: Bl4f4s3L4711$12345$
--
-- This stores a legacy MD5 hash temporarily. OpenClanCMS accepts it and
-- rehashes it with password_hash() on the next successful login.
-- Adjust the table prefix if your setup.php uses something other than "cs".

ALTER TABLE `cs_users`
  MODIFY `users_pwd` varchar(255) NOT NULL default '';

UPDATE `cs_users`
SET `users_pwd` = 'e386a57c3029ff04bdce4f8aa6cf122b'
WHERE `access_id` IN (
  SELECT `access_id`
  FROM `cs_access`
  WHERE `access_clansphere` = 5
);
