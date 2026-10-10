<?php

declare(strict_types=1);

namespace NhanAZ\OhMyTest;

use pocketmine\scheduler\Task;

class TestTask extends Task {

	private int $runs = 0;

	public function onRun(): void {
		if (++$this->runs >= 6) {
			$this->getHandler()?->cancel();
		}
	}
}
