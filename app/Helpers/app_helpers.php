<?php

function check_status($item, $status)
{
    if (key_exists($status, STATUS_LIST)) {
        if ($item === $status) {
            return 'selected';
        }else{
            return '';
        }
    }else{
        return '';
    }
}

function encrypt($value)
{
    $enc = \config\Services::encrypter();
    return bin2hex($enc->encrypt($value));
}

function decrypt($value)
{
    $enc = \config\Services::encrypter();
    return $enc->decrypt(hex2bin($value));
}