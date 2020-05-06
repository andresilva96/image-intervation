<?php

$txt = 'The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog?';

$string = '';

foreach (str_split($txt) as $letter) {
    $string .= $letter;
    var_dump($string);
}
