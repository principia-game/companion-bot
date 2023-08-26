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
	'token' => TOKEN,
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
$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($commands) {
	if ($message->author->bot) return;

	$args = $cmd = null;
	if (str_starts_with($message->content, PREFIX)) {
		$args = explode(' ', $message->content);
		$cmd = substr($args[0], 1);
	}

	// If message is a command and it exists in commands array, call it and return.
	if ($cmd && isset($commands[$cmd])) {
		try {
			$ret = $commands[$cmd]($discord, $message, $args);
		} catch (PDOException $e) {
			if (defined('DEBUG_CHANNEL'))
				$discord->getChannel(DEBUG_CHANNEL)->sendMessage(substr($e, 0, 1984));
			else
				print($e);

			return;
		}

		if ($ret instanceof Embed)
			$message->channel->sendMessage(MessageBuilder::new()->addEmbed($ret));
		elseif (is_string($ret))
			$message->channel->sendMessage($ret);

		return;
	}

	// Blåhaj corrector
	if (str_contains(strtolower($message->content), 'blahaj')) {
		$message->channel->sendMessage(sprintf(
			"Hey <@%s>. What you are referring to as `blahaj` is in fact `blåhaj`, `BLÅHAJ`, or as it alternatively can be written, `blaahaj`.  The letter `Åå` is part of the Swedish alphabet but if it does not exist on your keyboard it is common to write it as `aa`. :point_up::nerd:",
		$message->author->id));
	}

	// Otherwise, increment XP if user is eligble:

	if (in_array($message->channel_id, IGNORED_CHANNELS)) return;

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
