<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

$cmd = getopt("h:a:");

if(isset($cmd['h']) || !isset($cmd['a'])) {
    die(usage());
}

$salt = base64_encode(openssl_random_pseudo_bytes(32));

define('PHP_DB_ENCRYPT', true);
define('PHP_DB_ENCRYPT_ALGORITHM', "{$cmd['a']}");
define('PHP_DB_ENCRYPT_SALT', "{$salt}");

print "MySQL Server: ";
$server = trim(fgets(STDIN));
print "MySQL User: ";
$user = trim(fgets(STDIN));
print "MySQL Password: ";
system("stty -echo");
$password = trim(fgets(STDIN));
system("stty echo");
$pwd = Godsgood33\Php_Db\Database::encrypt($password, $salt);

print PHP_EOL . "MySQL Schema: ";
$schema = trim(fgets(STDIN));

print <<<EOL

Copy and paste these to your configuration file, they can be used to encrypt and decrypt the password and any other fields

define('PHP_DB_SERVER', '{$server}');
define('PHP_DB_USER', '{$user}');
define('PHP_DB_PWD', '{$pwd}');
define('PHP_DB_SCHEMA', '{$schema}');
define('PHP_DB_ENCRYPT', true);
define('PHP_DB_ENCRYPT_ALGORITHM', '{$cmd['a']}');
define('PHP_DB_ENCRYPT_SALT', '{$salt}');

EOL;

function usage()
{
    $algorithms = implode(",", openssl_get_cipher_methods(false));

    return <<<EOL
You must include the algorithm that you want to use for the encryption with a "-a=''" parameter

$algorithms


EOL;
}