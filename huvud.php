<?php
require('config.php');
include __DIR__.'/vendor/autoload.php';
require('mysql.php');

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\Embed\Embed;
use Discord\Parts\User\Activity;

$cache = [
	'known_ids' => [],
	'last_rewarded_msg' => []
];

$levels = query("SELECT id, xp FROM levels");

while ($level = $levels->fetch()) {
	$cache['known_ids'][$level['id']] = true;
}

function getXP() {
	return rand(15, 25);
}

$discord = new Discord([
	'token' => $config['token'],
	'intents' => Intents::getDefaultIntents()
	  | Intents::MESSAGE_CONTENT,
]);

$discord->on('init', function (Discord $discord) use ($config, $cache) {
	global $cache;

	echo "Bot is ready!", PHP_EOL;

	$activity = new Activity($discord, [
		'name' => "Principia",
		'type' => Activity::TYPE_GAME,
	]);
	$discord->updatePresence($activity);

	// Listen for messages.
	$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($config, $cache) {
		global $cache;

		if ($message->author->bot) return;

		$msg = $message->content;
		$author = $message->author;
		$uid = $message->author->id;

		if ($msg === '!rank') {

			$xp = result("SELECT xp FROM levels WHERE id = ?", [$uid]);

			$mbd = new Embed($discord);
			$mbd->setTitle(sprintf(
					'Stats for %s (%s) ',
				$author->displayname, $author->username))
				->setDescription(":sparkles: ".$xp." Server XP");

		} else if ($msg === '!top') {

			$top = query("SELECT id,xp FROM levels ORDER BY xp DESC");

			$names = $xps = [];

			while ($user = $top->fetch()) {
				$names[] = '<@'.$user['id'].'>';
				$xps[] = $user['xp'];
			}

			$mbd = new Embed($discord);
			$mbd->setTitle(":sparkles: **Top 10 Members** :sparkles:")
				->addFieldValues('Name', join("\n", $names), true)
				->addFieldValues('XP', join("\n", $xps), true);

		} else if ($msg === '!help') {

			return "What, are you hanging off a cliff or something?";

		} else {
			if (in_array($message->channel_id, $config['ignored_channels'])) return;

			$lastRewarded = $cache['last_rewarded_msg'][$uid] ?? 0;
			if ($lastRewarded+60 < time()) {

				if (isset($cache['known_ids'][$uid])) {
					query("UPDATE levels SET xp = xp + ? WHERE id = ?",
						[getXP(), $uid]);
				} else {
					insertInto('levels', [
						'id' => $message->author->id,
						'xp' => getXP()
					]);

					$cache['known_ids'][$uid] = true;
				}

				$cache['last_rewarded_msg'][$uid] = time();
			}
		}

		if (isset($mbd)) {
			$message->channel->sendEmbed($mbd);
		}

		echo "{$message->author->username}: {$message->content}", PHP_EOL;
	});
});

$discord->run();
