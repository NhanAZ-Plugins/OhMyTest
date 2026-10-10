<?php

declare(strict_types=1);

namespace NhanAZ\OhMyTest;

class TextAZ {

	public static function center(string $str): string {
		$lines = explode("\n", $str);
		$maxLen = max(array_map('strlen', $lines));
		$result = '';
		foreach ($lines as $line) {
			$numSpaces = $maxLen - strlen($line);
			$result .= str_repeat(' ', (int)($numSpaces / 2)) . $line . str_repeat(' ', (int)($numSpaces / 2)) . "\n";
		}
		return $result;
	}

	public static function isOnline(string $playerName): bool {
		if (Main::getInstance()->getServer()->getPlayerExact($playerName) !== null) {
			return true;
		}
		return false;
	}
}
