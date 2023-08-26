<?php

$commands = [];

use Discord\Parts\Embed\Embed;
use function Discord\getColor;

define('EMBED_CLR', getColor("#7ba0a0"));

$commands['rank'] = function($discord, $message) {
	$user = $message->author;

	$xp = result("SELECT xp FROM levels WHERE id = ?", [$user->id]);

	$rank = 0;
	$levels = query("SELECT id FROM levels ORDER BY xp DESC");
	while ($level = $levels->fetch()) {
		$rank++;

		if ($level['id'] == $user->id) break;
	}

	$mbd = new Embed($discord);
	$mbd->setTitle(sprintf(
			'Stats for %s (%s) ',
		$user->displayname, $user->username))
		->setDescription(
			":sparkles: $xp Server XP\n".
			":medal: Rank #$rank")
		->setThumbnail($user->getAvatarAttribute())
		->setColor(EMBED_CLR);

	return $mbd;
};

$commands['top'] = function($discord, $message, $args) {
	$page = (int)($args[1] ?? 1);

	 // 100 pages means 2k members. If this ever needs to be increased then we're suffering from success.
	$page = clamp($page, 0, 100);

	$top = query("SELECT id,xp FROM levels ORDER BY xp DESC ".paginate($page, 20));

	$names = $xps = [];

	$rank = ($page-1)*20;

	while ($user = $top->fetch()) {
		$rank++;
		$names[] = '`'.leftpad($rank, 3).'.` <@'.$user['id'].'>';
		$xps[] = '`'.leftpad(fmtnum($user['xp']), 7).'`';
	}

	$pagelbl = ($page > 1 ? " (Page $page)" : '');

	$mbd = new Embed($discord);
	$mbd->setTitle(":sparkles: **Top 20 Members**$pagelbl :sparkles:")
		->addFieldValues('Rank  Name', join("\n", $names), true)
		->addFieldValues('XP', join("\n", $xps), true)
		->setColor(EMBED_CLR);

	return $mbd;
};

$commands['help'] = function() {
	return "What, are you hanging off a cliff or something?";
};
