<?php

declare(strict_types=1);

namespace cosmicpe\floatingtext\world;

use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\event\Listener;

final class WorldEventListener implements Listener{

	/**
	 * @param LevelLoadEvent $event
	 * @priority LOWEST
	 */
	public function onWorldLoad(LevelLoadEvent $event) : void{
		WorldManager::add($event->getLevel());
	}

	/**
	 * @param LevelUnloadEvent $event
	 * @priority LOWEST
	 */
	public function onWorldUnload(LevelUnloadEvent $event) : void{
		WorldManager::remove($event->getLevel());
	}

	/**
	 * @param ChunkLoadEvent $event
	 * @priority LOWEST
	 */
	public function onChunkLoad(ChunkLoadEvent $event) : void{
		$chunk = $event->getChunk();
		WorldManager::get($event->getLevel())->onChunkLoad($chunk->getX(), $chunk->getZ());
	}
}