<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="integer")
     */
    private $active_status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_date;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated_date;

    /**
     * @ORM\OneToMany(targetEntity=UserSetting::class, mappedBy="user", orphanRemoval=true)
     */
    private $userSettings;

    private static $validActiveStatuses = [
      0 => true, // inactive
      1 => true  // active
    ];

    public static $validFields = [
      'name' => true,
      'email' => true,
      'active_status' => true,
      'created_date' => true,
      'updated_date' => true,
      'user_settings' => true
    ];

    public function __construct()
    {
        $this->userSettings = new ArrayCollection();
    }

    public static function isValidField(string $name): ?bool
    {
      return array_key_exists($name, self::$validFields) && self::$validFields[$name];
    }

    public static function isValueActiveStatus(int $activeStatus): ?bool
    {
      return self::$validActiveStatuses[$activeStatus];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getActiveStatus(): ?int
    {
        return $this->active_status;
    }

    public function setActiveStatus(int $active_status): self
    {
        $this->active_status = $active_status;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->created_date;
    }

    public function setCreatedDate(\DateTimeInterface $created_date): self
    {
        $this->created_date = $created_date;

        return $this;
    }

    public function getUpdatedDate(): ?\DateTimeInterface
    {
        return $this->updated_date;
    }

    public function setUpdatedDate(\DateTimeInterface $updated_date): self
    {
        $this->updated_date = $updated_date;

        return $this;
    }

    /**
     * @return Collection|UserSetting[]
     */
    public function getUserSettings(): Collection
    {
        return $this->userSettings;
    }

    public function addUserSetting(UserSetting $userSetting): self
    {
        if (!$this->userSettings->contains($userSetting)) {
            $this->userSettings[] = $userSetting;
            $userSetting->setUser($this);
        }

        return $this;
    }

    public function removeUserSetting(UserSetting $userSetting): self
    {
        if ($this->userSettings->removeElement($userSetting)) {
            // set the owning side to null (unless already changed)
            if ($userSetting->getUser() === $this) {
                $userSetting->setUser(null);
            }
        }

        return $this;
    }

    public function toArray()
    {
        return [
          'id' => $this->getId(),
          'name' => $this->getName(),
          'active_status' => $this->getActiveStatus(),
          'created_date' => $this->getCreatedDate()->getTimestamp(),
          'updated_date' => $this->getUpdatedDate()->getTimestamp()
        ];
    }

}
