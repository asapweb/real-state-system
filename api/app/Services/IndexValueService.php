<?php

namespace App\Services;

use App\Models\IndexValue;
use App\Models\IndexType;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class IndexValueService
{
    /**
     * Create a new index value
     */
    public function create(array $data): IndexValue
    {
        return DB::transaction(function () use ($data) {
            $indexType = IndexType::findOrFail($data['index_type_id']);

            // Validar campos
            $this->validateFields($data);

            // Verificar duplicados
            $this->checkForDuplicates($data, $indexType);

            $indexValue = IndexValue::create($data);
            $indexValue->load('indexType');

            return $indexValue;
        });
    }

    /**
     * Update an existing index value
     */
    public function update(IndexValue $indexValue, array $data): IndexValue
    {
        return DB::transaction(function () use ($indexValue, $data) {
            $indexType = $indexValue->indexType;

            // Si se está cambiando el tipo de índice, obtener el nuevo
            if (isset($data['index_type_id']) && $data['index_type_id'] !== $indexValue->index_type_id) {
                $indexType = IndexType::findOrFail($data['index_type_id']);
            }

            // Validar campos
            $this->validateFields($data);

            // Verificar duplicados
            $this->checkForDuplicates($data, $indexType, $indexValue);

            $indexValue->update($data);
            $indexValue->load('indexType');

            return $indexValue;
        });
    }

    /**
     * Delete an index value
     */
    public function delete(IndexValue $indexValue): bool
    {
        return $indexValue->delete();
    }

    /**
     * Validar solo effective_date
     */
    private function validateFields(array $data): void
    {
        if (!isset($data['effective_date']) || !strtotime($data['effective_date'])) {
            throw new \InvalidArgumentException('El campo effective_date es requerido y debe ser una fecha válida');
        }
    }

    /**
     * Verificar duplicados solo por effective_date
     */
    private function checkForDuplicates(array $data, IndexType $indexType, ?IndexValue $excludeValue = null): void
    {
        $query = IndexValue::where('index_type_id', $data['index_type_id']);

        if ($excludeValue) {
            $query->where('id', '!=', $excludeValue->id);
        }

        $existingValue = $query->where('effective_date', $data['effective_date'])->first();
        if ($existingValue) {
            if (!$excludeValue || $existingValue->id !== $excludeValue->id) {
                throw new \InvalidArgumentException("Ya existe un valor para este tipo de índice en la fecha {$data['effective_date']}");
            }
        }
    }

    /**
     * Obtener valores filtrando por effective_date
     */
    public function getByFilters(array $filters = [])
    {
        $query = IndexValue::with('indexType');

        if (isset($filters['index_type_id'])) {
            $query->where('index_type_id', $filters['index_type_id']);
        }
        if (isset($filters['effective_date_from'])) {
            $query->where('effective_date', '>=', $filters['effective_date_from']);
        }
        if (isset($filters['effective_date_to'])) {
            $query->where('effective_date', '<=', $filters['effective_date_to']);
        }

        return $query->get();
    }

    /**
     * Get the latest value for a specific index type
     */
    public function getLatestValue(int $indexTypeId): ?IndexValue
    {
        return IndexValue::where('index_type_id', $indexTypeId)
            ->orderBy('effective_date', 'desc')
            ->with('indexType')
            ->first();
    }
}
