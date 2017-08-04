<?php

if (!function_exists('compressByNameId')) {
    function compressByNameId(Array $array)
    {
        return collect($array)->keyBy('id')->map(function($i){return $i['name'];})->sort();
    }
}