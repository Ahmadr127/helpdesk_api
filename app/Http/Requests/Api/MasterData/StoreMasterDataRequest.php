<?php

namespace App\Http\Requests\Api\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->route('type');

        return match ($type) {
            'departments' => [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:departments,code',
                'location_id' => 'nullable|exists:locations,id',
                'building_id' => 'nullable|exists:buildings,id',
                'status' => 'required|boolean',
            ],
            'buildings' => [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:buildings,code',
                'status' => 'required|boolean',
            ],
            'locations' => [
                'name' => 'required|string|max:255',
                'status' => 'required|boolean',
            ],
            'positions' => [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:positions,code',
                'status' => 'required|boolean',
            ],
            default => [
                'name' => 'required|string|max:255',
                'status' => 'required|boolean',
            ]
        };
    }
}
