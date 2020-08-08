<?php

declare(strict_types=1);

namespace cosmicpe\floatingtext;

use Closure;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\Player;
use pocketmine\utils\UUID;

class FloatingTextEntity extends Entity
{

    public const NETWORK_ID = EntityIds::ARMOR_STAND;

    public $height = 0.01;
    public $width = 0.01;
    public $gravity = 0.0;
    public $canCollide = false;
    public $keepMovement = true;
    protected $gravityEnabled = false;
    protected $drag = 0.0;
    protected $baseOffset = 1.62;

    /** @var UUID */
    protected $uuid;

    /** @var int */
    private $floating_text_id;

    /** @var FloatingText */
    private $floating_text;

    /** @var Closure[] */
    private $despawn_callbacks = [];

    public function __construct(Level $world, CompoundTag $nbt, int $floating_text_id, FloatingText $text)
    {
        $this->setCanSaveWithChunk(false);
        $this->uuid = UUID::fromRandom();
        $this->floating_text_id = $floating_text_id;
        $this->floating_text = $text;
        parent::__construct($world, $nbt);
    }

    protected function initEntity(): void
    {
        parent::initEntity();
        $this->setNameTagAlwaysVisible(true);
    }

    public function addDespawnCallback(Closure $callback): void
    {
        $this->despawn_callbacks[spl_object_id($callback)] = $callback;
    }

    public function getFloatingTextId(): int
    {
        return $this->floating_text_id;
    }

    public function getFloatingText(): FloatingText
    {
        return $this->floating_text;
    }

    protected function sendSpawnPacket(Player $player): void
    {

        $add = new PlayerListPacket();
        $add->type = PlayerListPacket::TYPE_ADD;
        $add->entries = [
            PlayerListEntry::createAdditionEntry(
                $this->uuid,
                $this->getId(),
                "",
                SkinAdapterSingleton::get()->toSkinData(new Skin("Standard_Custom", str_repeat("\x00", 8192))))
        ];
        $player->sendDataPacket($add);

        $pk = new AddPlayerPacket();
        $pk->uuid = $this->uuid;
        $pk->username = $this->getNameTag();
        $pk->entityRuntimeId = $this->getId();
        $pk->position = $this->asVector3();
        $pk->motion = $this->getMotion();
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->item = Item::get(Item::AIR, 0, 0);
        $pk->metadata = $this->propertyManager->getAll();
        $player->sendDataPacket($pk);

        $remove = new PlayerListPacket();
        $remove->type = PlayerListPacket::TYPE_REMOVE;
        $remove->entries = [
            PlayerListEntry::createRemovalEntry($this->uuid)
        ];
        $player->sendDataPacket($remove);

        $pk = new PlayerSkinPacket();
        $pk->uuid = $this->uuid;
        $pk->skin = SkinAdapterSingleton::get()->toSkinData(new Skin("Standard_Custom", str_repeat("\x00", 8192)));
        $player->sendDataPacket($pk);
        $player->dataPacket($pk);
    }

    public function isFireProof(): bool
    {
        return true;
    }

    public function canBeCollidedWith(): bool
    {
        return false;
    }

    protected function checkBlockCollision(): void
    {
    }

    public function canCollideWith(Entity $entity): bool
    {
        return false;
    }

    public function canBeMovedByCurrents(): bool
    {
        return false;
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->setCancelled();
    }

    public function onUpdate(int $currentTick): bool
    {
        return false;
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        return false;
    }

    public function setNameTag(string $name): void
    {
        parent::setNameTag($name);
        $this->sendData($this->hasSpawned, $this->propertyManager->getDirty());
        $this->propertyManager->clearDirtyProperties();
    }

    public function close(): void
    {
        parent::close();
        foreach ($this->despawn_callbacks as $callback) {
            $callback();
        }
        $this->despawn_callbacks = [];
    }
}
