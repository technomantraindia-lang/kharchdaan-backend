<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $this->getCart();
        $products = Product::whereIn('id', array_keys($cart))->with('brand')->get()->keyBy('id');

        $items = [];
        $subtotal = 0;

        foreach ($cart as $productId => $row) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $price = (float) ($product->sale_price ?? $product->price);
            $qty = (int) ($row['qty'] ?? 1);
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            $items[] = [
                'product' => $product,
                'qty' => $qty,
                'price' => $price,
                'line_total' => $lineTotal,
            ];
        }

        return view('frontend.cart.index', compact('items', 'subtotal'));
    }

    public function add(Request $request, Product $product)
    {
        $qty = max(1, (int) $request->input('qty', 1));
        $cart = $this->getCart();

        if (! isset($cart[$product->id])) {
            $cart[$product->id] = ['qty' => 0];
        }

        $cart[$product->id]['qty'] += $qty;
        $this->saveCart($cart);

        return back()->with('success', "{$product->name} added to cart.");
    }

    public function buyNow(Request $request, Product $product)
    {
        $this->add($request, $product);

        return redirect()->route('cart.index')->with('success', 'Proceed to checkout from your cart.');
    }

    public function remove(Product $product)
    {
        $cart = $this->getCart();
        unset($cart[$product->id]);
        $this->saveCart($cart);

        return back()->with('success', 'Item removed from cart.');
    }

    private function getCart(): array
    {
        return session()->get('cart', []);
    }

    private function saveCart(array $cart): void
    {
        session()->put('cart', $cart);
    }
}
