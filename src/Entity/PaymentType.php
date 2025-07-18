<?php

namespace App\Entity;

use App\Repository\PaymentTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentTypeRepository::class)]
class PaymentType
{
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'paymentType')]
    private Collection $orders;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $paymentTypeName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaymentTypeName(): ?string
    {
        return $this->paymentTypeName;
    }

    public function setPaymentTypeName(string $paymentTypeName): static
    {
        $this->paymentTypeName = $paymentTypeName;

        return $this;
    }

    public function getOrders(): Collection
    {
        return $this->orders;
    }
}
