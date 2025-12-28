<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CollectionOperationUploadRequest;
use App\Http\Requests\CollectionRequest;
use App\Jobs\BulkAddProductsToCollectionJob;
use App\Jobs\BulkRemoveProductsFromCollectionJob;
use App\Jobs\ProcessCollectionOperationJob;
use App\Models\Collection;
use App\Models\CollectionOperationJob;
use App\Services\CollectionCsvProcessor;
use App\Validators\CollectionOperationValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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


    public function uploadCsv(CollectionOperationUploadRequest $request, int $id): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $collection = $this->findCollection($id, $merchantId);

        if (!$collection) {
            return $this->notFoundResponse('Collection not found');
        }

        try {
            $file = $request->file('file');
            $operationType = $request->input('operation_type');
            $filename = $file->getClientOriginalName();

            $filePath = $file->storeAs(
                "collection-operations/merchant-{$merchantId}",
                uniqid('operation_') . '_' . $filename,
                'local'
            );

            $processor = new CollectionCsvProcessor(
                Storage::path($filePath),
                new CollectionOperationValidator()
            );
            $totalRows = $processor->countRows();

            $operationJob = CollectionOperationJob::create([
                'merchant_id' => $merchantId,
                'collection_id' => $collection->id,
                'operation_type' => $operationType,
                'filename' => $filename,
                'file_path' => $filePath,
                'total_rows' => $totalRows,
                'status' => 'queued',
            ]);

            ProcessCollectionOperationJob::dispatch($operationJob);

            return $this->successResponse(
                $this->formatOperationJob($operationJob),
                'CSV upload successful. Processing started.',
                202
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to upload CSV: ' . $e->getMessage(),
                500
            );
        }
    }


    public function listOperations(Request $request, int $id): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $collection = $this->findCollection($id, $merchantId);

        if (!$collection) {
            return $this->notFoundResponse('Collection not found');
        }

        $operations = CollectionOperationJob::where('collection_id', $id)
            ->where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginatedResponse($operations, fn($operation) => $this->formatOperationJob($operation));
    }


    public function getOperationStatus(Request $request, int $id, int $operationId): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $collection = $this->findCollection($id, $merchantId);

        if (!$collection) {
            return $this->notFoundResponse('Collection not found');
        }

        $operation = CollectionOperationJob::where('id', $operationId)
            ->where('collection_id', $id)
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$operation) {
            return $this->notFoundResponse('Operation not found');
        }

        return $this->successResponse($this->formatOperationJobDetails($operation));
    }


    public function getOperationErrors(Request $request, int $id, int $operationId): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $collection = $this->findCollection($id, $merchantId);

        if (!$collection) {
            return $this->notFoundResponse('Collection not found');
        }

        $operation = CollectionOperationJob::where('id', $operationId)
            ->where('collection_id', $id)
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$operation) {
            return $this->notFoundResponse('Operation not found');
        }

        $errors = $operation->logs()
            ->orderBy('row_number', 'asc')
            ->paginate(50);

        return $this->paginatedResponse($errors, fn($error) => $this->formatOperationError($error));
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


    private function formatOperationJob(CollectionOperationJob $operation): array
    {
        return [
            'id' => $operation->id,
            'collection_id' => $operation->collection_id,
            'operation_type' => $operation->operation_type,
            'filename' => $operation->filename,
            'status' => $operation->status,
            'total_rows' => $operation->total_rows,
            'processed_rows' => $operation->processed_rows,
            'successful_rows' => $operation->successful_rows,
            'failed_rows' => $operation->failed_rows,
            'stock_added' => $operation->stock_added,
            'stock_removed' => $operation->stock_removed,
            'progress_percentage' => $operation->getProgressPercentage(),
            'created_at' => $operation->created_at->toIso8601String(),
            'started_at' => $operation->started_at?->toIso8601String(),
            'completed_at' => $operation->completed_at?->toIso8601String(),
        ];
    }


    private function formatOperationJobDetails(CollectionOperationJob $operation): array
    {
        return [
            'id' => $operation->id,
            'collection_id' => $operation->collection_id,
            'collection_name' => $operation->collection->name,
            'operation_type' => $operation->operation_type,
            'filename' => $operation->filename,
            'status' => $operation->status,
            'total_rows' => $operation->total_rows,
            'processed_rows' => $operation->processed_rows,
            'successful_rows' => $operation->successful_rows,
            'failed_rows' => $operation->failed_rows,
            'stock_added' => $operation->stock_added,
            'stock_removed' => $operation->stock_removed,
            'progress_percentage' => $operation->getProgressPercentage(),
            'created_at' => $operation->created_at->toIso8601String(),
            'started_at' => $operation->started_at?->toIso8601String(),
            'completed_at' => $operation->completed_at?->toIso8601String(),
        ];
    }


    private function formatOperationError($error): array
    {
        return [
            'id' => $error->id,
            'row_number' => $error->row_number,
            'row_data' => $error->row_data,
            'error_type' => $error->error_type,
            'error_message' => $error->error_message,
            'created_at' => $error->created_at->toIso8601String(),
        ];
    }
}
