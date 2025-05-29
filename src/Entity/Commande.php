<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CommandeRepository;


#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Product::class)]
    private $products;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateCommande;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->dateCommande = new \DateTime();  // Default to current date/time
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getProducts()
    {
        return $this->products;
    }
    public function getDateCommande(): \DateTimeInterface
    {
        return $this->dateCommande;
    }


    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }
        return $this;
    }

    public function removeProduct(Product $product): self
    {
        $this->products->removeElement($product);
        return $this;
    }

    // Function to calculate the sum of the prices in the cart (panier)
    public function getTotalPrice(): float
    {
        $total = 0;
        foreach ($this->products as $product) {
            $total += $product->getPrice();
        }
        return $total;
    }
}
