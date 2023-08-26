<?php
require('config.php');
require('vendor/autoload.php');
require('cache.php');
require('commands.php');
require('functions.php');
require('sqlite.php');

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\User\Activity;

{
	Cache::init('last_rewarded_msg');
	Cache::init('known_ids');

	$levels = query("SELECT id, xp FROM levels");

	while ($level = $levels->fetch())
		Cache::set('known_ids', $level['id'], true);
}

$discord = new Discord([
	'token' => $config['token'],
	'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT
]);

$discord->on('init', function (Discord $discord) {
	echo "Bot is ready!", PHP_EOL;

	$activity = new Activity($discord, [
		'name' => "Principia",
		'type' => Activity::TYPE_GAME,
	]);
	$discord->updatePresence($activity);
});

// Listen for messages.
$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($config, $commands) {
	if ($message->author->bot) return;

	$args = $cmd = null;
	if (str_starts_with($message->content, '!')) {
		$args = explode(' ', $message->content);
		$cmd = substr($args[0], 1);
	}

	// If message is a command and it exists in commands array, call it and return.
	if ($cmd && isset($commands[$cmd])) {
		$ret = $commands[$cmd]($discord, $message, $args);

		if ($ret instanceof Embed)
			$message->channel->sendMessage(MessageBuilder::new()->addEmbed($ret));
		elseif (is_string($ret))
			$message->channel->sendMessage($ret);

		return;
	}

	// Otherwise, increment XP if user is eligble:

	if (in_array($message->channel_id, $config['ignored_channels'])) return;

	$uid = $message->author->id;

	$lastRewarded = Cache::get('last_rewarded_msg', $uid);
	if ($lastRewarded+60 < time()) {

		if (Cache::get('known_ids', $uid)) {
			query("UPDATE levels SET xp = xp + ? WHERE id = ?",
				[getXP(), $uid]);
		} else {
			insertInto('levels', [
				'id' => $uid,
				'xp' => getXP()
			]);

			Cache::set('known_ids', $uid, true);
		}

		Cache::set('last_rewarded_msg', $uid, time());
	}
});

$discord->run();
