<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationRequest;
use App\Models\LocationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LocationTypeController extends Controller
{
    private const LOCATION_APPROVER_EMP_ID = '000-000167';

    private function canManageLocationTypes(): bool
    {
        $user = getAuthUser();

        return $user->hasRole('Operation Analystis')
            || $user->emp_id === self::LOCATION_APPROVER_EMP_ID;
    }

    private function ensureOperationAnalyst()
    {
        if (! $this->canManageLocationTypes()) {
            abort(response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to manage location types.',
            ], 403));
        }
    }

    public function index(Request $request)
    {
        $query = LocationType::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureOperationAnalyst();

        $validate = Validator::make($request->all(), [
            'code' => 'required|string|max:100|unique:location_types,code',
            'name' => 'required|string|max:255',
            'category' => 'required|in:sale,warehouse',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validate->errors(),
            ], 422);
        }

        $type = LocationType::create([
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'category' => $request->category,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Location type created.',
            'data' => $type,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureOperationAnalyst();

        $type = LocationType::findOrFail($id);

        $validate = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('location_types', 'code')->ignore($type->id),
            ],
            'name' => 'required|string|max:255',
            'category' => 'required|in:sale,warehouse',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validate->errors(),
            ], 422);
        }

        $type->update([
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'category' => $request->category,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Location type updated.',
            'data' => $type->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $this->ensureOperationAnalyst();

        $type = LocationType::findOrFail($id);

        $inUse = LocationRequest::where('location_category', $type->code)->exists();
        if ($inUse) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete this location type because it is used in location requests.',
            ], 409);
        }

        $type->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Location type deleted.',
        ]);
    }
}
