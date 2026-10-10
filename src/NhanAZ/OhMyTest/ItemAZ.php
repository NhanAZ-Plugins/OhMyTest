<?php

declare(strict_types=1);

namespace NhanAZ\OhMyTest;

use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class ItemAZ {
	use SingletonTrait;

	// $nametag = $this->simpleRegisterVanillaItem('Nametag', ItemTypeNames::NAME_TAG);

	// public static function _registryRegister(string $name, object $member): void {
	// 	self::verifyName($name);
	// 	$upperName = mb_strtoupper($name);
	// 	if (isset(self::$members[$upperName])) {
	// 		throw new \InvalidArgumentException("\"$upperName\" is already reserved");
	// 	}
	// 	self::$members[$upperName] = $member;
	// }

	/** @param list<string> $stringToItemParserNames */
	public static function registerSimpleItem(string $namespaceId, Item $item, array $stringToItemParserNames): void {
		GlobalItemDataHandlers::getDeserializer()->map($namespaceId, fn () => clone $item);
		GlobalItemDataHandlers::getSerializer()->map($item, fn () => new SavedItemData($namespaceId));

		foreach ($stringToItemParserNames as $name) {
			StringToItemParser::getInstance()->register($name, fn () => clone $item);
		}
	}

	public function simpleRegisterVanillaItem(string $displayName, string $namespaceId): Item {
		$item = new Item(new ItemIdentifier(ItemTypeIds::newId()), $displayName);
		ItemAZ::getInstance()->registerSimpleItem($namespaceId, $item, [$namespaceId]);
		return $item;
	}
}
