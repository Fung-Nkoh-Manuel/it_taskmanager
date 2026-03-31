-- Fix user passwords to use the correct hash for "password"
-- This migration can be run safely multiple times

UPDATE users 
SET password = '$2y$12$mUmCFN6AJHVnXt2NpEe6dejS.S8nVClsak42Lajkegk2BIFzwMH3q' 
WHERE username IN ('admin', 'technicien1', 'technicien2', 'user1')
  AND password != '$2y$12$mUmCFN6AJHVnXt2NpEe6dejS.S8nVClsak42Lajkegk2BIFzwMH3q';