<?php
require('config.php');
require('sqlite.php');

$levels = json_decode(file_get_contents('data.json'));

foreach ($levels as $level) {
	insertInto('levels', ['id' => $level->id, 'xp' => $level->xp]);
}
