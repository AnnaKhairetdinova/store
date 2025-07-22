<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Создание нового заказа
     * @throws ValidationException
     */
    public function createOrder(string $userUuid, array $products, ?string $comment = null): Order
    {
        foreach ($products as $item) {
            $product = Product::find($item['uuid']);

            if (!$product) {
                throw ValidationException::withMessages([
                    'products' => "Товар не найден"
                ]);
            }

            if ($product->stock < $item['quantity']) {
                throw ValidationException::withMessages([
                    'products' => "Недостаточно товара"
                ]);
            }
        }

        $total = 0;
        foreach ($products as $item) {
            $product = Product::findOrFail($item['uuid']);
            $total += $product->price * $item['quantity'];
        }

        // делаем через транзакцию чтобы никакие данные не потерялись
        return DB::transaction(function () use ($userUuid, $products, $comment, $total) {
            $order = Order::create([
                'user_uuid' => $userUuid,
                'comment' => $comment,
                'status' => OrderStatus::New,
                'amount' => $total,
            ]);

            foreach ($products as $item) {
                $product = Product::findOrFail($item['uuid']);

                $order->orderItems()->create([
                    'product_uuid' => $product->uuid,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                // уменьшаем наличие товара
                $product->decrement('stock', $item['quantity']);
            }

            return $order;
        });
    }

    /**
     * Заказы пользователя
     */
    public function getOrdersByUserUuid(string $userUuid, int $limit = 10)
    {
        return Order::where('user_uuid', $userUuid)
            ->with(['orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
}
