<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "app_users_details",
 *          parameters = { 
 *              "customer" = "expr(object.getCustomer().getId())",
 *              "user" = "expr(object.getId())" 
 *          },
 *          absolute = false
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getUsers")
 * )
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "app_users_update",
 *          parameters = { 
 *              "customer" = "expr(object.getCustomer().getId())",
 *              "user" = "expr(object.getId())" 
 *          },
 *          absolute = false
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups={"getUsers", "getUserDetails"})
 * )
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "app_users_delete",
 *          parameters = { 
 *              "customer" = "expr(object.getCustomer().getId())",
 *              "user" = "expr(object.getId())" 
 *          },
 *          absolute = false
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups={"getUsers", "getUserDetails"})
 * )
 * @Hateoas\Relation(
 *      "create",
 *      href = @Hateoas\Route(
 *          "app_users_create",
 *          parameters = { 
 *              "customer" = "expr(object.getCustomer().getId())"
 *          },
 *          absolute = false
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getUsers")
 * )
 * 
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(
 *  fields={"username"},
 *  message="Ce nom d'utilisateur existe déjà."
 * )
 * @UniqueEntity(
 *  fields={"email"},
 *  message="Cet e-mail est déjà utilisé."
 * )
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"getUsers", "getUserDetails"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="users")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"getUserDetails"})
     */
    private $customer;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Le nom d'utilisateur doit être renseigné.")
     * @Assert\Length(
     *      min=2,
     *      max=255,
     *      minMessage="Le nom de l'utilisateur doit contenir au moins deux caractères.",
     *      maxMessage="Le nom de l'utilisateur ne peut pas dépasser 255 caractères."
     * )
     * @Groups({"getUsers", "getUserDetails"})
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="L'adresse e-mail doit être renseignée.")
     * @Assert\Email(
     *      message = "L'adresse e-mail n'est pas valide."
     * )
     * @Assert\Length(
     *      max = 255,
     *      maxMessage = "L'adresse email ne peut pas contenir plus de {{ limit }} caractères."
     * )
     * @Groups({"getUserDetails"})
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     * @Groups({"getUserDetails"})
     */
    private $roles = [];

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"getUserDetails"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"getUserDetails"})
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $token_validity;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenValidity(): ?\DateTimeInterface
    {
        return $this->token_validity;
    }

    public function setTokenValidity(?\DateTimeInterface $tokenValidity): self
    {
        $this->token_validity = $tokenValidity;
        return $this;
    }

    // ============================================================================================
    // USER INTERFACE
    // ============================================================================================
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
    
    public function eraseCredentials()
    {
        
    }

    public function getSalt() : ?string
    {
        return null;
    }

    public function getPassword(): ?string
    {
        return $this->getToken();
    }

}
