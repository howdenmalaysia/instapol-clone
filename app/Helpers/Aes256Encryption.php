<?php
namespace App\Helpers;
class Aes256Encryption{
    const CIPHER = 'AES-256-CBC';
    const ITERATION = 10;
    const KEY_LENGTH = 32;

    public static function secured_encrypt($input){
        $password='';
        $salt='';
        $ivSpec='1234567890123456'; // default empty
        $first_key = openssl_pbkdf2($password, $salt, static::KEY_LENGTH, static::ITERATION, "sha1");
        $first_encrypted =openssl_encrypt($input,static::CIPHER,$first_key, OPENSSL_RAW_DATA,$ivSpec);
        $output = base64_encode($first_encrypted);
        return $output;
    }
    public static function secured_decrypt($input){
        $password='';
        $salt='';
        $ivSpec='1234567890123456';
        $first_key = openssl_pbkdf2($password, $salt, static::KEY_LENGTH, static::ITERATION, "sha1");
        $mix = base64_decode($input);
        $data = openssl_decrypt($mix,static::CIPHER,$first_key,OPENSSL_RAW_DATA,$ivSpec);
        return $data;
    }
}
//example to use
// $encrypted = Aes256Encryption::secured_encrypt("AmGeneral Insurance");
// $decrypted = Aes256Encryption::secured_decrypt("gCZYeXctYD8UJRRTmnITqxHwQReIw3jGdlUOt1zcyHo=");