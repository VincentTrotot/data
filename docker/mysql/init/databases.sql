# create databases
CREATE DATABASE IF NOT EXISTS `app`;
CREATE DATABASE IF NOT EXISTS `app_test`;

# create root user and grant rights
CREATE USER 'root'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%';