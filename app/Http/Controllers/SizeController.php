<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSizeRequest;
use App\Product;
use App\Size;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    /**
     * @param Product $product
     * @return mixed
     */
    public function index(Product $product) {
        return $product->sizes;
    }

    /**
     * @param Product $product
     * @param CreateSizeRequest $request
     * @return JsonResponse
     */
    public function store(Product $product, CreateSizeRequest $request): JsonResponse
    {
        Size::create([
            'display_name' => $request->display_name,
            'price' => $this->calculatePrice($product, $request),
            'product_id' => $request->product_id,
            'operator' => $request->operator,
            'price_adjustment' => $request->price_adjustment
        ]);

        return response()->json(['message' => 'Size was created.']);
    }

    /**
     * @param Size $size
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Size $size): JsonResponse
    {
        $size->delete();
        return response()->json(['message' => 'Size was deleted.']);
    }

    protected function calculatePrice(Product $product, Request $request)
    {
        $price = $product->price;
        if ($request->operator && $request->price_adjustment) {
            if ($request->operator === '+') {
                $price+= $request->price_adjustment;
            }
            if ($request->operator === '-') {
                $price-= $request->price_adjustment;
            }
        }
        return $price;
    }
}
