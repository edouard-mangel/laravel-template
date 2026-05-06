<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Events\ProductUpdated;
use App\Domain\Shared\AggregateRoot;
use DateTimeImmutable;

final class Product extends AggregateRoot
{
    private function __construct(
        private readonly ProductId $id,
        private ProductName $name,
        private ProductSku $sku,
        private ProductPrice $price,
        private readonly string $ownerId,
    ) {}

    public static function create(
        ProductId $id,
        ProductName $name,
        ProductSku $sku,
        ProductPrice $price,
        string $ownerId,
    ): self {
        $product = new self($id, $name, $sku, $price, $ownerId);

        $product->recordEvent(new ProductCreated(
            productId: $id,
            name: $name->value,
            priceInCents: $price->valueInCents,
            occurredAt: new DateTimeImmutable(),
        ));

        return $product;
    }

    public static function reconstitute(
        ProductId $id,
        ProductName $name,
        ProductSku $sku,
        ProductPrice $price,
        string $ownerId,
    ): self {
        return new self($id, $name, $sku, $price, $ownerId);
    }

    public function update(ProductName $name, ProductPrice $price): void
    {
        $this->name = $name;
        $this->price = $price;

        $this->recordEvent(new ProductUpdated(
            productId: $this->id,
            name: $name->value,
            priceInCents: $price->valueInCents,
            occurredAt: new DateTimeImmutable(),
        ));
    }

    public function id(): ProductId { return $this->id; }
    public function name(): ProductName { return $this->name; }
    public function sku(): ProductSku { return $this->sku; }
    public function price(): ProductPrice { return $this->price; }
    public function ownerId(): string { return $this->ownerId; }
}
