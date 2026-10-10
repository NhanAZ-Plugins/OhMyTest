<?php

declare(strict_types=1);

namespace NhanAZ\OhMyTest;

use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\permission\PermissionManager;

class DumpAZ {

	const GRID = 16; // 16 * 16 Emoji

	public static function Glyph(string $glyph = "E0"): void {
		$filename = basename("glyph_$glyph", ".png");
		$file = fopen(Main::getInstance()->getDataFolder() . "/Glyph.md", "w");
		if ($file === false) {
			throw new \RuntimeException("Unable to write Glyph.md");
		}
		fwrite($file, "# glyph_$glyph.png" . "\n");
		$startChar = hexdec(substr($filename, strrpos($filename, "_") + 1) . "00");
		$messages = array_fill(0, self::GRID * self::GRID, "");
		for ($i = 0; $i < self::GRID * self::GRID; $i++) {
			$z = intdiv($i, self::GRID);
			$ci = (int) $startChar + $i; // char index
			$messages[$z] .= mb_chr($ci) . " ";
		}
		foreach ($messages as $key => $row) {
			if ($key < 16) {
				fwrite($file, $row . "<br/>" . "\n");
			}
		}
		fclose($file);
	}

	public static function Permissions(): void {
		$permissions = PermissionManager::getInstance()->getPermissions();
		$file = fopen(Main::getInstance()->getDataFolder() . "/permissions.md", "w");
		if ($file === false) {
			throw new \RuntimeException("Unable to write permissions.md");
		}
		fwrite($file, "| Name | Description | Children | Permissibles |" . "\n");
		fwrite($file, "| :--- | :---------- | :------- | :----------- |" . "\n");
		foreach ($permissions as $permission) {
			$lang = new Language("eng");
			$description = $permission->getDescription();
			$descriptionString = $description instanceof Translatable ? $lang->translate($description) : $description;

			$childrens = $permission->getChildren();
			$children = json_encode($childrens, JSON_THROW_ON_ERROR);
			$children = str_replace(",", "<br/>", $children);
			$children = str_replace(["{", "}"], "", $children);
			$children = str_replace('":', " : ", $children);
			$children = str_replace('"', "- ", $children);
			if ($children !== "[]") {
				$children = "<details><summary>Details</summary> " . $children . "</details>";
			}

			$permissibles = $permission->getPermissibles();
			$permissionle = json_encode($permissibles, JSON_THROW_ON_ERROR);
			fwrite($file, "| `" . $permission->getName() . "` | " . $descriptionString . " | " . $children . " | " . $permissionle . " |" . "\n");
		}
		fclose($file);
	}

	public static function IdItem(): void {
		$arr = [];
		$file = fopen(Main::getInstance()->getDataFolder() . "/ID Items.md", "w");
		if ($file === false) {
			throw new \RuntimeException("Unable to write ID Items.md");
		}
		fwrite($file, "| ID:Meta | (TypeId:ComputeStateData)xCount tags:0xNamedTag |" . "\n");
		fwrite($file, "| :------ | :---------------------------------------------- |" . "\n");
		for ($id = -500; $id <= 1000; $id++) {
			for ($meta = 0; $meta <= 200; $meta++) {
				echo "DumpAZ::IdItem running #$id/1000 $meta/200\n";
				$item = $id . ":" . $meta;
				try {
					$item = StringToItemParser::getInstance()->parse($item) ?? LegacyStringToItemParser::getInstance()->parse($item);
				} catch (LegacyStringToItemParserException $e) {
					$item = "Unknown";
				}
				if ($item instanceof Item) {
					$object = $item->getStateId() . $item->getTypeId();
					if (!in_array($object, $arr)) {
						fwrite($file, "| `" . $id . ":" . $meta . "` | " . $item->__toString() . " |" . "\n");
						array_push($arr, $object);
					}
				}
			}
		}
		fclose($file);
	}
}
