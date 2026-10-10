<?php

declare(strict_types=1);

namespace NhanAZ\OhMyTest;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

class Main extends PluginBase implements Listener {
    use SingletonTrait;

    private const PERMISSION = 'ohmytest.experimental';
    private const CHAT_COMMANDS = ['tp', 'nbt', 'setnbt', 'egd', 'vd', 'ws', 'fly', 'pause', 'resume'];

    protected function onEnable(): void {
        self::setInstance($this);
        if ($this->getConfig()->get('enable-experimental-features', false) !== true) {
            $this->getLogger()->notice('Experimental features are disabled in config.yml.');
            return;
        }

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleRepeatingTask(new TestTask(), 20);
        WorldAZ::loadWorlds();
        WorldAZ::generateTerrain();
        DumpAZ::Glyph();
        DumpAZ::Permissions();
    }

    public function onChat(PlayerChatEvent $event): void {
        $parts = preg_split('/\s+/', trim($event->getMessage())) ?: [];
        $command = $parts[0] ?? '';
        if (!in_array($command, self::CHAT_COMMANDS, true)) {
            return;
        }

        $event->cancel();
        $player = $event->getPlayer();
        if (!$player->hasPermission(self::PERMISSION)) {
            $player->sendMessage('This experimental command requires operator permission.');
            return;
        }

        switch ($command) {
            case 'tp':
                $worldName = $parts[1] ?? '';
                if ($worldName === '') {
                    $player->sendMessage('Usage: tp <world>');
                    return;
                }
                $world = $this->getServer()->getWorldManager()->getWorldByName($worldName);
                if (!$world instanceof World) {
                    $player->sendMessage("World not loaded: $worldName");
                    return;
                }
                $player->teleport($world->getSafeSpawn());
                $player->sendMessage('Moved to ' . $world->getDisplayName());
                return;

            case 'nbt':
                $tag = $player->getInventory()->getItemInHand()->getCustomBlockData();
                $player->sendMessage($tag?->getString('blockdata', 'No blockdata value') ?? 'No blockdata value');
                return;

            case 'setnbt':
                $key = $parts[1] ?? '';
                $value = implode(' ', array_slice($parts, 2));
                if ($key === '' || $value === '') {
                    $player->sendMessage('Usage: setnbt <key> <value>');
                    return;
                }
                $item = $player->getInventory()->getItemInHand();
                $tag = $item->getCustomBlockData() ?? new CompoundTag();
                $tag->setString($key, $value);
                $item->setCustomBlockData($tag);
                $player->getInventory()->setItemInHand($item);
                return;

            case 'egd':
                $item = VanillaBlocks::OAK_LOG()->asItem();
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3));
                $item->setCustomName('Custom test item');
                $item->setCount(30);
                $player->getInventory()->addItem($item);
                return;

            case 'vd':
                $item = $player->getInventory()->getItemInHand();
                $player->sendMessage($item->getName() . ' x' . $item->getCount() . ' (type ' . $item->getTypeId() . ')');
                return;

            case 'ws':
                $names = [];
                foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
                    $names[] = $world->getDisplayName();
                }
                $player->sendMessage(implode(', ', $names));
                return;

            case 'fly':
                $player->setAllowFlight(true);
                $player->setFlying(true);
                return;

            case 'pause':
                $this->broadcastPause($player, 1);
                return;

            case 'resume':
                $this->broadcastPause($player, 0);
                return;
        }
    }

    private function broadcastPause(Player $player, int $state): void {
        $packet = LevelEventPacket::create(LevelEvent::PAUSE_GAME, $state, $player->getPosition());
        NetworkBroadcastUtils::broadcastPackets($this->getServer()->getOnlinePlayers(), [$packet]);
        $player->sendMessage($state === 1 ? 'Paused.' : 'Resumed.');
    }
}
