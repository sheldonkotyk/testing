<?php

//This function checks to make sure the input is a list of integers, no letters allowed!
Validator::extend('integers', function ($attribute, $value, $parameters) {

    foreach (explode(',', $value) as $v) {
        if (!ctype_digit(strval($v))) {
            return false;
        }
    }
    return true;
});

Validator::extend('alpha_num_spaces', function ($attribute, $value) {
    return preg_match('/^[a-z0-9 ]+$/i', $value);
});


 //This function disallows any blank spaces or values less than 1 in a comma separated list
Validator::extend('no_blanks', function ($attribute, $value, $parameters) {
    
     $nums= explode(',', $value);

    foreach ($nums as $num) {
        if (empty($num)) {
            return false;
        }
        if ($num<1) {
            return false;
        }
    }

    return true;
});
