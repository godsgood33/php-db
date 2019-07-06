<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

$cmd = getopt("h::a::", ['algorithms::', 'help::']);

if(isset($cmd['h']) || isset($cmd['help'])) {
    die(usage());
} elseif(isset($cmd['algorithms'])) {
    die(algorithms());
}

$encrypt = isset($cmd['a']);
if($encrypt) {
    $salt = base64_encode(openssl_random_pseudo_bytes(32));
    $algorithm = $cmd['a'];
} else {
    $algorithm = '';
    $salt = '';
}

define('PHP_DB_ENCRYPT', $encrypt);
define('PHP_DB_ENCRYPT_ALGORITHM', $algorithm);
define('PHP_DB_ENCRYPT_SALT', $salt);

print "MySQL Server: ";
$server = trim(fgets(STDIN));
print "MySQL User: ";
$user = trim(fgets(STDIN));
$password = getPassword();
$pwd = ($encrypt ? Godsgood33\Php_Db\Database::encrypt($password, $salt) : $password);

print PHP_EOL . "MySQL Schema: ";
$schema = trim(fgets(STDIN));

$encrypt = ($encrypt ? 'true' : 'false');

print <<<EOL

Copy and paste these to your bootstrap file, they can be used to encrypt and decrypt the password and any other fields

define('PHP_DB_SERVER', '{$server}');
define('PHP_DB_USER', '{$user}');
define('PHP_DB_PWD', '{$pwd}');
define('PHP_DB_SCHEMA', '{$schema}');
define('PHP_DB_ENCRYPT', {$encrypt});
define('PHP_DB_ENCRYPT_ALGORITHM', '{$algorithm}');
define('PHP_DB_ENCRYPT_SALT', '{$salt}');

EOL;

function usage()
{

    return <<<EOL
Purpose: Run a setup for PHP_DB mysqli helper

Usage: php setup.php -a={algorithm} [--algorithms] [--encrypt] [-h|--help]

 --algorithms       Print out a list list of all the OpenSSL algorithms this system supports

 -a={algorithm}     The OpenSSL algorithm used to encrypt the password
 
 
 -h|--help          This help screen


EOL;
}

/**
 * Function to print out all the supported OpenSSL encryption algorithms for the given system
 * 
 * @return string
 */
function algorithms()
{
    $algorithms = implode(",", array_iunique(openssl_get_cipher_methods(false)));
    
    return $algorithms . PHP_EOL;
}

/**
 * Function to strip duplicates from an array case-insensitively
 * 
 * @return array
 */
function array_iunique($array)
{
    return array_intersect_key(
        $array,
        array_unique(array_map('strtolower', $array))
    );
}

function getPassword()
{
    if(preg_match('/^win/i', PHP_OS)) {
        $vbscript = sys_get_temp_dir() . '\prompt_password.vbs';
        file_put_contents(
            $vbscript, 'wscript.echo(InputBox("' .
                addslashes("MySQL Password: ") .
                '", "", "password here"))'
            );
        $cmd = "cscript //nologo " . escapeshellarg($vbscript);
        $password = rtrim(shell_exec($cmd));
        unlink($vbscript);
    } else {
        system("stty -echo");
        $password = trim(fgets(STDIN));
        system("stty echo");
    }

    return $password;
}