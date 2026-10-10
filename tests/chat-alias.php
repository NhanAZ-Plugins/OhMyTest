<?php

declare(strict_types=1);

namespace pocketmine\event {
	interface Listener {}
}

namespace pocketmine\permission {
	final class Permission {}
}

namespace pocketmine\player {
	use pocketmine\permission\Permission;

	final class Player {
		public bool $allowed = false;
		public bool $allowFlight = false;
		public bool $flying = false;
		/** @var list<string> */
		public array $messages = [];

		public function hasPermission(Permission|string $permission): bool {
			return $this->allowed && $permission === 'ohmytest.experimental';
		}

		public function sendMessage(string $message): void {
			$this->messages[] = $message;
		}

		public function setAllowFlight(bool $allowed): void {
			$this->allowFlight = $allowed;
		}

		public function setFlying(bool $flying): void {
			$this->flying = $flying;
		}
	}
}

namespace pocketmine\event\player {
	use pocketmine\player\Player;

	final class PlayerChatEvent {
		private bool $cancelled = false;

		public function __construct(private Player $player, private string $message) {}

		public function getPlayer(): Player {
			return $this->player;
		}

		public function getMessage(): string {
			return $this->message;
		}

		public function cancel(): void {
			$this->cancelled = true;
		}

		public function isCancelled(): bool {
			return $this->cancelled;
		}
	}
}

namespace pocketmine\plugin {
	class PluginBase {}
}

namespace pocketmine\utils {
	trait SingletonTrait {}
}

namespace {
	require __DIR__ . '/../src/NhanAZ/OhMyTest/Main.php';

	use NhanAZ\OhMyTest\Main;
	use pocketmine\event\player\PlayerChatEvent;
	use pocketmine\player\Player;

	function check(bool $condition, string $message): void {
		if (!$condition) {
			throw new RuntimeException($message);
		}
	}

	function chat(Main $plugin, Player $player, string $message): PlayerChatEvent {
		$event = new PlayerChatEvent($player, $message);
		$plugin->onChat($event);
		return $event;
	}

	$plugin = new Main();
	$player = new Player();
	check(!chat($plugin, $player, 'hello world')->isCancelled(), 'Ordinary chat must be preserved');
	check(chat($plugin, $player, 'fly')->isCancelled(), 'A recognized alias must not reach normal chat');
	check($player->messages === ['This experimental command requires operator permission.'], 'Unprivileged player must receive denial');
	check(!$player->allowFlight && !$player->flying, 'Denied alias must not change flight');

	$player->allowed = true;
	check(chat($plugin, $player, 'tp')->isCancelled(), 'Missing world argument must be handled');
	check(chat($plugin, $player, 'setnbt')->isCancelled(), 'Missing NBT arguments must be handled');
	check($player->messages === [
		'This experimental command requires operator permission.',
		'Usage: tp <world>',
		'Usage: setnbt <key> <value>'
	], 'Missing argument usage messages must be precise');
	check(chat($plugin, $player, 'fly')->isCancelled(), 'Authorized alias must be removed from chat');
	check($player->allowFlight && $player->flying, 'Authorized flight alias must enable flight');

	echo "PASS: chat alias recognition, permission gating, missing arguments and authorized flight\n";
}
