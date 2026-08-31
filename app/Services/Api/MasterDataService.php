<?php

namespace App\Services\Api;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Position;
use App\Models\UnitProses;
use Illuminate\Database\Eloquent\Model;

class MasterDataService
{
    protected array $map = [
        'categories' => Category::class,
        'departments' => Department::class,
        'buildings' => Building::class,
        'locations' => Location::class,
        'unit-proses' => UnitProses::class,
        'unit_proses' => UnitProses::class,
        'positions' => Position::class,
    ];

    public function getModelClass(string $type): string
    {
        $type = strtolower($type);
        if (!isset($this->map[$type])) {
            throw new \Exception("Tipe data tidak valid: {$type}", 422);
        }
        return $this->map[$type];
    }

    public function list(string $type, array $filters = [], int $perPage = 15)
    {
        $class = $this->getModelClass($type);
        $query = $class::query();

        if ($type === 'categories') {
            $query->with('unitProses');
        }
        if ($type === 'locations') {
            $query->with('building');
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->where('name','like',"%{$filters['search']}%");
        }
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at','>=',$filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at','<=',$filters['to_date']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function find(string $type, int $id): Model
    {
        $class = $this->getModelClass($type);
        $model = $class::find($id);
        if (!$model) throw new \Exception("Data tidak ditemukan", 404);
        if ($type === 'categories') $model->load('unitProses');
        if ($type === 'locations') $model->load('building');
        return $model;
    }

    public function create(string $type, array $data): Model
    {
        $class = $this->getModelClass($type);
        return $class::create($data);
    }

    public function update(string $type, int $id, array $data): Model
    {
        $model = $this->find($type, $id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(string $type, int $id): void
    {
        $model = $this->find($type, $id);
        $model->delete();
    }

    public function bulkAction(string $type, string $action, array $ids): int
    {
        $class = $this->getModelClass($type);
        $query = $class::whereIn('id', $ids);
        switch($action){
            case 'activate': return $query->update(['status'=>1]);
            case 'deactivate': return $query->update(['status'=>0]);
            case 'delete': return $query->delete();
            default: throw new \Exception('Action tidak valid',422);
        }
    }
}
