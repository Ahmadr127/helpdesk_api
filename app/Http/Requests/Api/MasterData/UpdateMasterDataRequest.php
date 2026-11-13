<?php

namespace App\Http\Requests\Api\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->route('type');
        $id = $this->route('id');

        return match ($type) {
            'departments' => [
                'name' => 'required|string|max:255',
                'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')->ignore($id)],
                'location_id' => 'nullable|exists:locations,id',
                'building_id' => 'nullable|exists:buildings,id',
                'status' => 'required|boolean',
            ],
            'buildings' => [
                'name' => 'required|string|max:255',
                'code' => ['required', 'string', 'max:50', Rule::unique('buildings', 'code')->ignore($id)],
                'status' => 'required|boolean',
            ],
            'locations' => [
                'name' => 'required|string|max:255',
                'status' => 'required|boolean',
            ],
            'positions' => [
                'name' => 'required|string|max:255',
                'code' => ['required', 'string', 'max:50', Rule::unique('positions', 'code')->ignore($id)],
                'status' => 'required|boolean',
            ],
            default => [
                'name' => 'required|string|max:255',
                'status' => 'required|boolean',
            ]
        };
    }
}
