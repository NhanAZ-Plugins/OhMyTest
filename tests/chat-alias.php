<?php

declare(strict_types=1);

namespace pocketmine\event {
	interface Listener {}
}

namespace pocketmine\permission {
	final class Permission {}
}

namespace pocketmine\nbt\tag {
	final class CompoundTag {
		/** @var array<string, string> */
		private array $values = [];

		public function setString(string $key, string $value): void {
			$this->values[$key] = $value;
		}

		public function getString(string $key, string $default): string {
			return $this->values[$key] ?? $default;
		}
	}
}

namespace pocketmine\inventory {
	use pocketmine\item\Item;

	final class Inventory {
		private Item $item;

		public function __construct() {
			$this->item = new Item();
		}

		public function getItemInHand(): Item {
			return clone $this->item;
		}

		public function setItemInHand(Item $item): void {
			$this->item = clone $item;
		}
	}
}

namespace pocketmine\item {
	use pocketmine\nbt\tag\CompoundTag;

	final class Item {
		private ?CompoundTag $tag = null;

		public function __clone() {
			if ($this->tag !== null) {
				$this->tag = clone $this->tag;
			}
		}

		public function getCustomBlockData(): ?CompoundTag {
			return $this->tag;
		}

		public function setCustomBlockData(CompoundTag $tag): void {
			$this->tag = $tag;
		}
	}
}

namespace pocketmine\player {
	use pocketmine\inventory\Inventory;
	use pocketmine\permission\Permission;

	final class Player {
		public bool $allowed = false;
		public bool $allowFlight = false;
		public bool $flying = false;
		/** @var list<string> */
		public array $messages = [];
		private Inventory $inventory;

		public function __construct() {
			$this->inventory = new Inventory();
		}

		public function getInventory(): Inventory {
			return $this->inventory;
		}

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
	check(chat($plugin, $player, 'setnbt blockdata denied')->isCancelled(), 'Denied NBT alias must not reach normal chat');
	check($player->getInventory()->getItemInHand()->getCustomBlockData() === null, 'Denied NBT alias must not change the held item');

	$player->allowed = true;
	check(chat($plugin, $player, 'tp')->isCancelled(), 'Missing world argument must be handled');
	check(chat($plugin, $player, 'setnbt')->isCancelled(), 'Missing NBT arguments must be handled');
	check($player->messages === [
		'This experimental command requires operator permission.',
		'This experimental command requires operator permission.',
		'Usage: tp <world>',
		'Usage: setnbt <key> <value>'
	], 'Missing argument usage messages must be precise');
	check(chat($plugin, $player, 'fly')->isCancelled(), 'Authorized alias must be removed from chat');
	check($player->allowFlight && $player->flying, 'Authorized flight alias must enable flight');
	check(chat($plugin, $player, 'nbt')->isCancelled(), 'NBT inspection alias must be removed from chat');
	check(end($player->messages) === 'No blockdata value', 'Missing blockdata must be reported');
	check(chat($plugin, $player, 'setnbt blockdata hello world')->isCancelled(), 'NBT write alias must be removed from chat');
	check($player->getInventory()->getItemInHand()->getCustomBlockData()?->getString('blockdata', '') === 'hello world', 'NBT write must persist on the held item');
	chat($plugin, $player, 'setnbt blockdata');
	check($player->getInventory()->getItemInHand()->getCustomBlockData()?->getString('blockdata', '') === 'hello world', 'Incomplete NBT write must preserve the held item');
	chat($plugin, $player, 'setnbt other retained');
	check($player->getInventory()->getItemInHand()->getCustomBlockData()?->getString('other', '') === 'retained', 'NBT write must preserve existing custom data');
	chat($plugin, $player, 'nbt');
	check(end($player->messages) === 'hello world', 'NBT inspection must read the persisted blockdata value');

	echo "PASS: chat alias recognition, permission gating, flight and held-item NBT\n";
}
