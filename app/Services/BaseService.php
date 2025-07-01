<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Abstract Base Service
 * 
 * Provides common functionality and semantic patterns for all services.
 * Establishes consistent error handling, logging, and response formatting.
 */
abstract class BaseService
{
    /**
     * The model class this service manages
     */
    protected string $modelClass;
    
    /**
     * The resource class for API transformations
     */
    protected ?string $resourceClass = null;
    
    /**
     * Service context for logging
     */
    protected string $serviceContext;

    public function __construct()
    {
        $this->serviceContext = class_basename(static::class);
    }

    /**
     * Get all resources with optional filters
     */
    public function getAll(array $filters = [], array $relations = [])
    {
        try {
            $query = $this->modelClass::query();
            
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            // Apply filters semantically
            $this->applyFilters($query, $filters);
            
            $results = $query->latest()->get();
            
            // Transform results if resource class is defined
            if ($this->resourceClass) {
                return $this->resourceClass::collection($results);
            }
            
            return $results;
        } catch (\Exception $e) {
            $this->logError('Failed to retrieve all resources', $e, $filters);
            throw $e;
        }
    }

    /**
     * Find resource by ID with relations
     */
    public function findById(int $id, array $relations = []): ?Model
    {
        try {
            $query = $this->modelClass::query();
            
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            return $query->find($id);
        } catch (\Exception $e) {
            $this->logError("Failed to find resource by ID: {$id}", $e);
            throw $e;
        }
    }

    /**
     * Create a new resource
     */
    public function create(array $data): array
    {
        try {
            // Validate data if validation rules are defined
            $this->validateData($data, $this->getCreateValidationRules());
            
            // Process data before creation
            $processedData = $this->preprocessData($data, 'create');
            
            $resource = $this->modelClass::create($processedData);
            
            $this->logSuccess('Resource created successfully', [
                'resource_id' => $resource->id,
                'data' => $processedData
            ]);
            
            return $this->formatSuccessResponse(
                $resource,
                $this->getCreateSuccessMessage($resource)
            );
        } catch (ValidationException $e) {
            $this->logValidationError('Create validation failed', $e, $data);
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Failed to create resource', $e, $data);
            return $this->formatErrorResponse('Failed to create resource', $e);
        }
    }

    /**
     * Update an existing resource
     */
    public function update(Model $resource, array $data): array
    {
        try {
            // Validate data if validation rules are defined
            $this->validateData($data, $this->getUpdateValidationRules($resource));
            
            // Process data before update
            $processedData = $this->preprocessData($data, 'update', $resource);
            
            $resource->update($processedData);
            $updatedResource = $resource->fresh();
            
            $this->logSuccess('Resource updated successfully', [
                'resource_id' => $resource->id,
                'data' => $processedData
            ]);
            
            return $this->formatSuccessResponse(
                $updatedResource,
                $this->getUpdateSuccessMessage($updatedResource)
            );
        } catch (ValidationException $e) {
            $this->logValidationError('Update validation failed', $e, $data);
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Failed to update resource', $e, $data);
            return $this->formatErrorResponse('Failed to update resource', $e);
        }
    }

    /**
     * Delete a resource
     */
    public function delete(Model $resource): array
    {
        try {
            // Check if deletion is allowed
            $canDelete = $this->canDelete($resource);
            if (!$canDelete['allowed']) {
                return $this->formatErrorResponse($canDelete['reason']);
            }
            
            // Perform pre-deletion cleanup
            $this->beforeDelete($resource);
            
            $resource->delete();
            
            // Perform post-deletion cleanup
            $this->afterDelete($resource);
            
            $this->logSuccess('Resource deleted successfully', [
                'resource_id' => $resource->id
            ]);
            
            return $this->formatSuccessResponse(
                null,
                $this->getDeleteSuccessMessage($resource)
            );
        } catch (\Exception $e) {
            $this->logError('Failed to delete resource', $e);
            return $this->formatErrorResponse('Failed to delete resource', $e);
        }
    }

    /**
     * Standard success response format
     */
    protected function formatSuccessResponse($data = null, string $message = null, int $status = 200): array
    {
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }

    /**
     * Standard error response format
     */
    protected function formatErrorResponse(string $message, ?\Exception $exception = null, int $status = 400): array
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if ($exception && config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }
        
        return $response;
    }

    /**
     * Apply semantic filters to query
     */
    protected function applyFilters($query, array $filters): void
    {
        // Override in child services for specific filtering logic
    }

    /**
     * Preprocess data before create/update operations
     */
    protected function preprocessData(array $data, string $operation, ?Model $resource = null): array
    {
        // Override in child services for specific preprocessing
        return $data;
    }

    /**
     * Check if resource can be deleted
     */
    protected function canDelete(Model $resource): array
    {
        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Execute before deletion
     */
    protected function beforeDelete(Model $resource): void
    {
        // Override in child services for cleanup
    }

    /**
     * Execute after deletion
     */
    protected function afterDelete(Model $resource): void
    {
        // Override in child services for cleanup
    }

    /**
     * Get create validation rules
     */
    protected function getCreateValidationRules(): array
    {
        return [];
    }

    /**
     * Get update validation rules
     */
    protected function getUpdateValidationRules(Model $resource): array
    {
        return [];
    }

    /**
     * Get create success message
     */
    protected function getCreateSuccessMessage(Model $resource): string
    {
        return ucfirst($this->serviceContext) . ' created successfully';
    }

    /**
     * Get update success message
     */
    protected function getUpdateSuccessMessage(Model $resource): string
    {
        return ucfirst($this->serviceContext) . ' updated successfully';
    }

    /**
     * Get delete success message
     */
    protected function getDeleteSuccessMessage(Model $resource): string
    {
        return ucfirst($this->serviceContext) . ' deleted successfully';
    }

    /**
     * Validate data using Laravel validator
     */
    protected function validateData(array $data, array $rules): void
    {
        if (empty($rules)) {
            return;
        }
        
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Log success operations
     */
    protected function logSuccess(string $message, array $context = []): void
    {
        Log::info("{$this->serviceContext}: {$message}", $context);
    }

    /**
     * Log error operations
     */
    protected function logError(string $message, \Exception $exception, array $context = []): void
    {
        Log::error("{$this->serviceContext}: {$message}", [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'context' => $context,
        ]);
    }

    /**
     * Log validation errors
     */
    protected function logValidationError(string $message, ValidationException $exception, array $context = []): void
    {
        Log::warning("{$this->serviceContext}: {$message}", [
            'errors' => $exception->errors(),
            'context' => $context,
        ]);
    }
} 