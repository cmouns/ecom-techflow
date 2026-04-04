<?php

namespace App\Entity;

use App\Repository\DeliveryInfoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DeliveryInfoRepository::class)]
class DeliveryInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez donner un nom à cette adresse (ex: Domicile).')]
    #[Assert\Length(max: 255)]
    private string $label;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 255)]
    private string $firstName;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 255)]
    private string $lastName;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'adresse complète est obligatoire.')]
    #[Assert\Length(max: 255)]
    private string $address;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le code postal est obligatoire.')]
    #[Assert\Length(max: 20)]
    #[Assert\Regex(
        pattern: '/^[0-9A-Za-z\s\-]+$/',
        message: 'Le format du code postal est invalide.'
    )]
    private string $postalCode;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La ville est obligatoire.')]
    #[Assert\Length(max: 255)]
    private string $city;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le pays est obligatoire.')]
    #[Assert\Length(max: 255)]
    private string $country;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire pour la livraison.')]
    #[Assert\Length(max: 30)]
    #[Assert\Regex(
        pattern: '/^[0-9\+\-\s\.]+$/',
        message: 'Le format du numéro de téléphone est invalide.'
    )]
    private string $phone;

    #[ORM\ManyToOne(inversedBy: 'deliveryInfos')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
