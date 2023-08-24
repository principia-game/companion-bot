<?php
require('config.php');
require('sqlite.php');

$levels = query("SELECT id,xp FROM levels");

$data = [];
while ($level = $levels->fetch()) {
	$data[] = ['id' => $level['id'], 'xp' => $level['xp']];
}

file_put_contents('data.json', json_encode($data));
