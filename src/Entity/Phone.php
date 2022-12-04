<?php

namespace App\Entity;

use App\Repository\PhoneRepository;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Groups;

/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "app_phones_details",
 *          parameters = { "id" = "expr(object.getId())" },
 *          absolute = false
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getPhones")
 * )
 * 
 * @ORM\Entity(repositoryClass=PhoneRepository::class)
 */
class Phone
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"getPhones", "getPhoneDetails"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"getPhones", "getPhoneDetails"})
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=Brand::class, inversedBy="phones")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"getPhoneDetails"})
     */
    private $brand;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"getPhoneDetails"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"getPhoneDetails"})
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=50)
     * @Groups({"getPhoneDetails"})
     */
    private $color;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"getPhoneDetails"})
     */
    private $dual_sim;

    /**
     * @ORM\Column(type="float")
     * @Groups({"getPhoneDetails"})
     */
    private $memory;

    /**
     * @ORM\Column(type="text")
     * @Groups({"getPhoneDetails"})
     */
    private $description;

    /**
     * @ORM\Column(type="float")
     * @Groups({"getPhoneDetails"})
     */
    private $selling_price;

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

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $created): self
    {
        $this->createdAt = $created;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function isDualSim(): ?bool
    {
        return $this->dual_sim;
    }

    public function setDualSim(bool $dual_sim): self
    {
        $this->dual_sim = $dual_sim;

        return $this;
    }

    public function getMemory(): ?float
    {
        return $this->memory;
    }

    public function setMemory(float $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSellingPrice(): ?float
    {
        return $this->selling_price;
    }

    public function setSellingPrice(float $selling_price): self
    {
        $this->selling_price = $selling_price;

        return $this;
    }
}
