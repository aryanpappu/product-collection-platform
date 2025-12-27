<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CollectionRequest;
use App\Jobs\BulkAddProductsToCollectionJob;
use App\Jobs\BulkRemoveProductsFromCollectionJob;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);

        $collections = Collection::where('merchant_id', $merchantId)
            ->withCount('products')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginatedResponse($collections, fn($collection) => $this->formatCollection($collection));
    }


    public function show(Request $request, int $id): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $collection = $this->findCollection($id, $merchantId);

        if (!$collection) {
            return $this->notFoundResponse('Collection not found');
        }

        $collection->loadCount('products');

        return $this->successResponse($this->formatCollectionDetails($collection));
    }


    public function store(CollectionRequest $request): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);

        $collection = Collection::create([
            'merchant_id' => $merchantId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'slug' => $this->generateUniqueSlug($request->input('name'), $merchantId),
            'is_active' => $request->input('is_active', true),
        ]);

        return $this->successResponse(
            $this->formatCollection($collection),
            'Collection created successfully',
            201
        );
    }


    public function update(CollectionRequest $request, int $id): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $collection = $this->findCollection($id, $merchantId);

        if (!$collection) {
            return $this->notFoundResponse('Collection not found');
        }

        $updateData = [
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        // Update slug if name changed
        if ($request->input('name') !== $collection->name) {
            $updateData['slug'] = $this->generateUniqueSlug($request->input('name'), $merchantId, $id);
        }

        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->input('is_active');
        }

        $collection->update($updateData);

        return $this->successResponse(
            $this->formatCollection($collection),
            'Collection updated successfully'
        );
    }


    public function destroy(Request $request, int $id): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $collection = $this->findCollection($id, $merchantId);

        if (!$collection) {
            return $this->notFoundResponse('Collection not found');
        }

        $collection->delete();

        return $this->successResponse(
            null,
            'Collection deleted successfully'
        );
    }


    public function bulkAddProducts(Request $request, int $id): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $collection = $this->findCollection($id, $merchantId);

        if (!$collection) {
            return $this->notFoundResponse('Collection not found');
        }

        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|integer|min:1',
        ]);

        $productIds = $request->input('product_ids');

        BulkAddProductsToCollectionJob::dispatch($collection->id, $productIds, $merchantId);

        return $this->successResponse(
            [
                'collection_id' => $collection->id,
                'product_count' => count($productIds),
                'status' => 'queued',
            ],
            'Bulk add products job queued successfully',
            202
        );
    }


    public function bulkRemoveProducts(Request $request, int $id): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $collection = $this->findCollection($id, $merchantId);

        if (!$collection) {
            return $this->notFoundResponse('Collection not found');
        }

        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|integer|min:1',
        ]);

        $productIds = $request->input('product_ids');

        BulkRemoveProductsFromCollectionJob::dispatch($collection->id, $productIds, $merchantId);

        return $this->successResponse(
            [
                'collection_id' => $collection->id,
                'product_count' => count($productIds),
                'status' => 'queued',
            ],
            'Bulk remove products job queued successfully',
            202
        );
    }


    private function getMerchantId(Request $request): int
    {
        return $request->attributes->get('merchant_id');
    }


    private function findCollection(int $id, int $merchantId): ?Collection
    {
        return Collection::where('id', $id)
            ->where('merchant_id', $merchantId)
            ->first();
    }


    private function formatCollection(Collection $collection): array
    {
        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'slug' => $collection->slug,
            'is_active' => $collection->is_active,
            'products_count' => $collection->products_count ?? 0,
            'created_at' => $collection->created_at->toIso8601String(),
            'updated_at' => $collection->updated_at->toIso8601String(),
        ];
    }


    private function formatCollectionDetails(Collection $collection): array
    {
        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'slug' => $collection->slug,
            'is_active' => $collection->is_active,
            'products_count' => $collection->products_count ?? 0,
            'created_at' => $collection->created_at->toIso8601String(),
            'updated_at' => $collection->updated_at->toIso8601String(),
        ];
    }


    private function generateUniqueSlug(string $name, int $merchantId, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $merchantId, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }


    private function slugExists(string $slug, int $merchantId, ?int $excludeId = null): bool
    {
        $query = Collection::where('merchant_id', $merchantId)
            ->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
