<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "api_users_details",
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
 *          "api_users_update",
 *          parameters = { 
 *              "customer" = "expr(object.getCustomer().getId())",
 *              "user" = "expr(object.getId())" 
 *          },
 *          absolute = false
 *      ),
 *      exclusion = @Hateoas\Exclusion(
 *          groups={"getUsers", "getUserDetails"},
 *          excludeIf = "expr(not is_granted('ROLE_ADMIN'))"
 *      )
 * )
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "api_users_delete",
 *          parameters = { 
 *              "customer" = "expr(object.getCustomer().getId())",
 *              "user" = "expr(object.getId())" 
 *          },
 *          absolute = false
 *      ),
 *      exclusion = @Hateoas\Exclusion(
 *          groups={"getUsers", "getUserDetails"}, 
 *          excludeIf = "expr(not is_granted('ROLE_ADMIN'))"
 *      )
 * )
 * @Hateoas\Relation(
 *      "create",
 *      href = @Hateoas\Route(
 *          "api_users_create",
 *          parameters = { 
 *              "customer" = "expr(object.getCustomer().getId())"
 *          },
 *          absolute = false
 *      ),
 *      exclusion = @Hateoas\Exclusion(
 *          groups="getUsers", 
 *          excludeIf = "expr(not is_granted('ROLE_ADMIN'))"
 *      )
 * )
 * 
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(
 *  fields={"fullName"},
 *  message="Ce nom d'utilisateur (fullName) existe déjà."
 * )
 * @UniqueEntity(
 *  fields={"email"},
 *  message="Cet e-mail est déjà utilisé."
 * )
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
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
     * @ORM\Column(type="string", length=255, name="full_name")
     * @Assert\NotBlank(message="Le nom d'utilisateur (fullName) doit être renseigné.")
     * @Assert\Length(
     *      min=2,
     *      max=255,
     *      minMessage="Le nom de l'utilisateur (fullName) doit contenir au moins deux caractères.",
     *      maxMessage="Le nom de l'utilisateur (fullName) ne peut pas dépasser 255 caractères."
     * )
     * @Groups({"getUsers", "getUserDetails"})
     */
    private $fullName;

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
     * @var string The hashed password
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(
     *      message = "Vous devez choisir un mot de passe.",
     *      groups={"PasswordForm"}
     * )
     * @Assert\Length(
     *      min="8",
     *      max="254",
     *      minMessage="Le mot de passe doit faire entre 8 et 254 caractères.",
     *      maxMessage="Le mot de passe doit faire entre 8 et 254 caractères."
     * )
     * @Assert\Regex(
     *     pattern = "^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])^",
     *     match = true,
     *     message = "Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre."
     * )
     */
    private $password;

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

    public function getFullname(): ?string
    {
        return $this->fullName;
    }

    public function setFullname(string $fullname): self
    {
        $this->fullName = $fullname;

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

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

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

    // ============================================================================================
    // USER INTERFACE
    // ============================================================================================
    public function getUsername(): ?string
    {
        return (string) $this->getUserIdentifier();
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
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

}
