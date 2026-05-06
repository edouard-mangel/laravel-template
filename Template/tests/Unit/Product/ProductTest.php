<?php

use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Product;
use App\Domain\Product\ProductId;
use App\Domain\Product\ProductName;
use App\Domain\Product\ProductPrice;
use App\Domain\Product\ProductSku;
use App\Domain\Shared\Exceptions\InvalidInputException;

describe('Product', function (): void {
    describe('create', function (): void {
        it('records a ProductCreated event', function (): void {
            $product = Product::create(
                id: ProductId::generate(),
                name: new ProductName('Test Product'),
                sku: new ProductSku('SKU-001'),
                price: ProductPrice::fromCents(1000),
                ownerId: 'owner-1',
            );

            $events = $product->releaseEvents();

            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeInstanceOf(ProductCreated::class)
                ->and($events[0]->name)->toBe('Test Product');
        });

        it('releases events only once', function (): void {
            $product = Product::create(
                id: ProductId::generate(),
                name: new ProductName('Widget'),
                sku: new ProductSku('SKU-002'),
                price: ProductPrice::fromCents(500),
                ownerId: 'owner-1',
            );

            $product->releaseEvents();

            expect($product->releaseEvents())->toBeEmpty();
        });
    });

    describe('reconstitute', function (): void {
        it('does not record events when reconstituted from persistence', function (): void {
            $product = Product::reconstitute(
                id: ProductId::generate(),
                name: new ProductName('Persisted Product'),
                sku: new ProductSku('SKU-003'),
                price: ProductPrice::fromCents(2000),
                ownerId: 'owner-1',
            );

            expect($product->releaseEvents())->toBeEmpty();
        });
    });

    describe('ProductName', function (): void {
        it('rejects empty names', function (): void {
            expect(fn () => new ProductName(''))->toThrow(InvalidInputException::class);
        });

        it('rejects names over 255 characters', function (): void {
            expect(fn () => new ProductName(str_repeat('a', 256)))->toThrow(InvalidInputException::class);
        });

        it('accepts a valid name', function (): void {
            $name = new ProductName('Valid Name');
            expect($name->value)->toBe('Valid Name');
        });
    });

    describe('ProductPrice', function (): void {
        it('rejects negative prices', function (): void {
            expect(fn () => ProductPrice::fromCents(-1))->toThrow(InvalidInputException::class);
        });

        it('accepts zero price', function (): void {
            expect(ProductPrice::fromCents(0)->valueInCents)->toBe(0);
        });

        it('converts cents to float', function (): void {
            expect(ProductPrice::fromCents(1050)->toFloat())->toBe(10.50);
        });
    });
});
