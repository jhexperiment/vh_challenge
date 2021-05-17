<?php

namespace App\Entity;

use App\Repository\UserSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_settings")
 * @ORM\Entity(repositoryClass=UserSettingRepository::class)
 */
class UserSetting
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="userSettings")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $value;

    public static $validTypes = [
      'phone_number' => true,
      'email' => true,
      'address' => true,
      'social_media_link' => true
    ];

    public static function isValidType(string $type): ?bool
    {
      return array_key_exists($type, self::$validTypes) && self::$validTypes[$type];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function toArray()
    {
      
      return [
        'id' => $this->getId(),
        'user_id' => $this->getUser()->getId(),
        'type' => $this->getType(),
        'value' => $this->getValue()
      ];
    }
}
