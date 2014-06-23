<?php
function randVowel()
{
	$vowels = array("a", "e", "i", "o", "u");
	return $vowels[array_rand($vowels, 1)];
}

function randConsonant()
{
	$consonants = array("a", "b", "c", "d", "v", "g", "t");
	return $consonants[array_rand($consonants, 1)];
}

