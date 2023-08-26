<?php

function getXP() {
	return rand(15, 25);
}

function clamp($current, $min, $max) {
    return max($min, min($max, $current));
}

function commasep($str) {
	return implode(',', $str);
}

/**
 * Clean room reimplementation of JavaScript's left-pad in PHP.
 *
 * Warning: This function may disappear without notice!
 */
function leftpad($str, $len, $ch = " ") {
	return str_pad($str, $len, $ch, STR_PAD_LEFT);
}

/**
 * number_format() with sane defaults.
 */
function fmtnum($num, $decimals = 0) {
	return number_format($num, $decimals, ',', ' ');
}
