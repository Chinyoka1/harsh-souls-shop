<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Image;
use App\Product;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function index(Request $request): LengthAwarePaginator
    {
        $query = Product::with(['images', 'coverImage']);

        if ($request->filled('sortBy')) {
            $query->orderBy($request->sortBy[0], $request->sortDesc[0] === 'true' ? 'DESC' : 'ASC');
        }

        if ($request->filled('search')) {
            $query->where('display_name', 'LIKE', "%{$request->search}%");
        }

        return $query->paginate($request->itemsPerPage, '*', 'page', $request->page);
    }

    /**
     * @param Product $product
     * @return Builder|Builder[]|Collection|Model|null
     */
    public function show(Product $product)
    {
        return Product::with(['categories.parent', 'keywords', 'versions.image', 'discount', 'sizes',
            'images' => static function ($query) {
                $query->where('is_cover', '=', false);
            }])
            ->find($product->id);
    }

    /**
     * @param Product $product
     * @param UpdateProductRequest $request
     * @return JsonResponse
     */
    public function update(Product $product, UpdateProductRequest $request): JsonResponse
    {
        $product->display_name = $request->display_name;
        $product->price = $request->price;
        $product->is_active = $request->is_active;
        $product->description = $request->description;
        $product->color_code = $request->color_code;

        $product->save();

        return response()->json(['message' => 'Product details were updated.']);
    }

    /**
     * @param CreateProductRequest $request
     * @return JsonResponse
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        Product::create([
            'display_name' => $request->display_name,
            'price' => $request->price,
            'is_active' => $request->is_active,
            'description' => $request->description
        ]);

        return response()->json(['message' => 'Product was created.']);
    }

    /**
     * @param Product $product
     * @return mixed
     */
    public function getCoverImage(Product $product)
    {
        return $product->coverImage;
    }

    /**
     * todo: gehört in ImageController
     *
     * @param Image $image
     * @return JsonResponse
     */
    public function setCoverImage(Image $image): JsonResponse
    {
        $image->is_cover = true;
        $image->save();

        return response()->json(['message' => 'The new cover image is set.']);
    }

    /**
     * todo: gehört in ImageController
     *
     * @param Image $image
     * @return JsonResponse
     */
    public function unsetCoverImage(Image $image): JsonResponse
    {
        $image->is_cover = false;
        $image->save();

        return response()->json(['message' => 'The cover image is unset.']);
    }

    /**
     * @param Product $product
     * @param Request $request
     * @return JsonResponse
     */
    public function updateCategories(Product $product, Request $request): JsonResponse
    {
        $product->categories()->sync($request->categories);

        return response()->json(['message' => 'Product categories were updated.']);
    }

    /**
     * @return Builder|Model|object|null
     */
    public function getMaxPrice()
    {
        return Product::query()->where('is_active', '=', true)
            ->orderByDesc('price')->first('price');
    }

    /**
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function indexOverview(Request $request): LengthAwarePaginator
    {
        $query = Product::with(['images', 'versions', 'discount', 'sizes', 'coverImage'])
            ->where('is_active', '=', true);

        if ($request->categoryId) {
            $query->whereHas('categories', static function ($relation) use ($request) {
                return $relation->where('id', '=', $request->categoryId);
            });
        }
        if ($request->sort) {
            switch ($request->sort) {
                case 'newest products':
                {
                    $query->latest('created_at');
                    break;
                }
                case 'price descending':
                {
                    $query->orderByDesc('price');
                    break;
                }
                case 'price ascending':
                {
                    $query->orderBy('price');
                    break;
                }
                default:
                {
                    break;
                }
            }
        }

        if ($request->maxPrice) {
            $query->whereBetween('price', [$request->minPrice ?: 0, $request->maxPrice]);
        }
        if ($request->color) {
            $query->where('color_code', '=', $request->color);
        }
        if ($request->search) {
            $query->where('display_name', 'LIKE', "%{$request->search}%");
        }

        // OR WHERE

        if ($request->color) {
            $query->orWhereHas('versions', static function ($relation) use ($request) {
                return $relation->where('is_active', '=', true)
                    ->where('color_code', '=', $request->color);
            });
        }
        if ($request->search) {
            $query->orWhereHas('keywords', static function ($relation) use ($request) {
                return $relation->where('name', 'LIKE', "%{$request->search}%");
            });
        }

        return $query->paginate($request->limit ?: 18, '*', 'page', $request->page);
    }

    /**
     * @param Product $product
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return response()->json(['message' => 'Product was deleted.']);
    }
}
