<?php

$commands = [];

use Discord\Parts\Embed\Embed;
use function Discord\getColor;

define('EMBED_CLR', getColor("#7ba0a0"));

$commands['rank'] = function($discord, $message) {
	$author = $message->author;

	$xp = result("SELECT xp FROM levels WHERE id = ?", [$author->id]);

	$mbd = new Embed($discord);
	$mbd->setTitle(sprintf(
			'Stats for %s (%s) ',
		$author->displayname, $author->username))
		->setDescription(":sparkles: $xp Server XP")
		->setColor(EMBED_CLR);

	return $mbd;
};

$commands['top'] = function($discord, $message, $args) {
	$page = (int)($args[1] ?? 1);

	$top = query("SELECT id,xp FROM levels ORDER BY xp DESC ".paginate($page, 20));

	$names = $xps = [];

	while ($user = $top->fetch()) {
		$names[] = '<@'.$user['id'].'>';
		$xps[] = $user['xp'];
	}

	$pagelbl = ($page > 1 ? " (Page $page)" : '');

	$mbd = new Embed($discord);
	$mbd->setTitle(":sparkles: **Top 20 Members**$pagelbl :sparkles:")
		->addFieldValues('Name', join("\n", $names), true)
		->addFieldValues('XP', join("\n", $xps), true)
		->setColor(EMBED_CLR);

	return $mbd;
};

$commands['help'] = function() {
	return "What, are you hanging off a cliff or something?";
};
