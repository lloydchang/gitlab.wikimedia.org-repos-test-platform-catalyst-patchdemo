CREATE USER IF NOT EXISTS 'patchdemo'@'localhost' IDENTIFIED BY 'patchdemo';
GRANT ALL PRIVILEGES ON `patchdemo%`.* TO 'patchdemo'@'%';
FLUSH PRIVILEGES;
