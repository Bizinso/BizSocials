-- Create test database for automated testing
CREATE DATABASE IF NOT EXISTS bizsocials_testing;
GRANT ALL PRIVILEGES ON bizsocials_testing.* TO 'bizsocials'@'%';
FLUSH PRIVILEGES;
