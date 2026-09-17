<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Bay;
use App\Models\Row;
use App\Models\Side;
use App\Models\Zone;
use App\Models\Level;
use App\Models\Branch;
use App\Models\Location;
use App\Models\LocationHd;
use App\Models\LocationRequest;
use App\Models\LocationRequestDocument;
use App\Models\LocationRequestNotification;
use App\Models\StockTracking;
use App\Models\User;
use App\Models\UserBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\BayResource;
use App\Http\Resources\RowResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\ZoneResource;
use App\Http\Resources\LevelResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LocationImportTemplateExport;

class LocationController extends Controller
{
    private const LOCATION_APPROVER_EMP_ID = '000-000167';

    private function isLocationApprover($user = null): bool
    {
        $user = $user ?? getAuthUser();

        return $user && $user->emp_id === self::LOCATION_APPROVER_EMP_ID;
    }

    public function allBranches()
    {
        $branches = Branch::orderBy('branch_name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $branches->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->branch_name,
                    'short_name' => $branch->branch_short_name,
                    'code' => $branch->branch_code,
                ];
            })->values(),
        ]);
    }

    public function downloadImportTemplate(Request $request)
    {
        $branch = null;

        if ($request->filled('branch_id')) {
            $branch = Branch::find($request->input('branch_id'));
        }

        if (! $branch) {
            $branch = Branch::orderBy('branch_name')->first();
        }

        if (! $branch) {
            return response()->json([
                'status' => 'error',
                'message' => 'No branches found.',
            ], 404);
        }

        return Excel::download(
            new LocationImportTemplateExport($branch),
            'location_request_template.xlsx'
        );
    }

    public function index()
    {
        $zones = Zone::get();
        $rows = Row::get();
        $bays = Bay::get();
        $levels = Level::get();
        $sides = Side::get();

        $branches = UserBranch::with('branch')
            ->where('user_id', auth()->id())
            ->get()
            ->pluck('branch')
            ->unique('id')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'zones' => ZoneResource::collection($zones),
                'rows' => RowResource::collection($rows),
                'bays' => BayResource::collection($bays),
                'levels' => LevelResource::collection($levels),
                'sides' => $sides,
                'branches' => $branches->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->branch_name,
                        'short_name' => $branch->branch_short_name
                    ];
                }),
            ]
        ]);
    }


    public function store(Request $request)
    {


        $validate = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'zone_id' => 'required|exists:zones,id',
            'row_id' => 'required|exists:rows,id',
            'bay_id' => 'required|exists:bays,id',
            'level_id' => 'required|exists:levels,id',
            'location_type' => 'nullable|in:Sale,Warehouse,S,W',
            'side_id' => 'nullable|exists:sides,id',
            'side' => 'nullable|string|in:F,B,Natural,None',
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => 'error', 'message' => $validate->errors()]);
        }
        Log::info(['validate' => $validate->validated()]);

        $branch = Branch::findOrFail($request->branch_id);
        $zone = Zone::findOrFail($request->zone_id);
        $row = Row::findOrFail($request->row_id);
        $bay = Bay::findOrFail($request->bay_id);
        $level = Level::findOrFail($request->level_id);

        // Prefer explicit side name from frontend; fallback to side_id lookup
        if ($request->filled('side')) {
            $side_name = in_array($request->side, ['Natural', 'None'], true) ? '' : $request->side;
        } elseif ($request->filled('side_id')) {
            $side_name = Side::findOrFail($request->side_id)?->name ?? '';
        } else {
            $side_name = '';
        }
        Log::info([
            'side_name' => $side_name
        ]);
        // Create location name
        // If frontend provides location_type, use it; otherwise fallback to user role.
        $locationType = $request->input('location_type');
        $isSale = in_array($locationType, ['Sale', 'S'], true)
            ? true
            : (in_array($locationType, ['Warehouse', 'W'], true)
                ? false
                : getAuthUser()->getRoleNames()->contains('Sale'));

        if ($isSale) {
            if ($side_name !== '') {
                $locationName = "{$branch->branch_short_name}S_{$zone->name}_{$row->name}_{$side_name}_{$bay->name}_{$level->name}";
            } else {
                $locationName = "{$branch->branch_short_name}S_{$zone->name}_{$row->name}_{$bay->name}_{$level->name}";
            }
        } else {
            $locationName = "{$branch->branch_short_name}W_{$zone->name}_{$row->name}_{$bay->name}_{$level->name}";
        }

        if (Location::where('location_name', $locationName)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This location already exists.'
            ], 409);
        }
        // Save only location_name
        $location = new Location();
        $location->location_name = $locationName;
        $location->branch_id = $request->branch_id;
        $location->save();

        return response()->json(['status' => 'success', 'message' => 'Location successfully saved!']);
    }

    public function storeRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'location_category' => 'required|string|max:255',
            'zone_id' => 'required|exists:zones,id',
            'row_id' => 'required|exists:rows,id',
            'bay_id' => 'required|exists:bays,id',
            'level_id' => 'required|exists:levels,id',
            'location_type' => 'nullable|in:Sale,Warehouse,S,W',
            'side' => 'nullable|string|in:F,B,Natural,None',
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => 'error', 'message' => $validate->errors()]);
        }

        $branch = Branch::findOrFail($request->branch_id);
        $zone = Zone::findOrFail($request->zone_id);
        $row = Row::findOrFail($request->row_id);
        $bay = Bay::findOrFail($request->bay_id);
        $level = Level::findOrFail($request->level_id);

        $side_name = '';
        if ($request->filled('side')) {
            $side_name = in_array($request->side, ['Natural', 'None'], true) ? '' : $request->side;
        }

        $locationType = $request->input('location_type');
        $isSale = in_array($locationType, ['Sale', 'S'], true)
            ? true
            : (in_array($locationType, ['Warehouse', 'W'], true)
                ? false
                : getAuthUser()->getRoleNames()->contains('Sale'));

        $locationName = $this->buildLocationName(
            $branch,
            $zone,
            $row,
            $bay,
            $level,
            $isSale,
            $side_name
        );

        if (
            Location::where('location_name', $locationName)->exists() ||
            LocationRequest::where('location_name', $locationName)
                ->where('status', 'request')
                ->exists()
        ) {
            if (Location::where('location_name', $locationName)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This location code already exists in locations.',
                    'duplicate_in' => 'locations',
                ], 409);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'A pending request for this location code already exists.',
                'duplicate_in' => 'location_requests',
            ], 409);
        }

        $locationRequest = LocationRequest::create([
            'user_id' => auth()->id(),
            'branch_id' => $request->branch_id,
            'location_category' => $request->location_category,
            'location_type' => $isSale ? 'S' : 'W',
            'zone_id' => $request->zone_id,
            'row_id' => $request->row_id,
            'bay_id' => $request->bay_id,
            'level_id' => $request->level_id,
            'side' => $request->input('side'),
            'branch_short_name' => $branch->branch_short_name,
            'location_name' => $locationName,
            'status' => 'request',
        ]);

        $approver = User::where('emp_id', self::LOCATION_APPROVER_EMP_ID)->first();
        if ($approver) {
            LocationRequestNotification::create([
                'location_request_id' => $locationRequest->id,
                'user_id' => $approver->id,
                'message' => "New location request: {$locationName}",
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Location request submitted successfully!',
            'data' => $locationRequest,
        ]);
    }

    public function storeDocument(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'lines' => 'required|array|min:1',
            'lines.*.branch_id' => 'required|exists:branches,id',
            'lines.*.location_category' => 'required|string|max:255',
            'lines.*.zone_id' => 'required_without:lines.*.zone|nullable|exists:zones,id',
            'lines.*.zone' => 'required_without:lines.*.zone_id|nullable|string|max:20',
            'lines.*.row_id' => 'required|exists:rows,id',
            'lines.*.bay_id' => 'required|exists:bays,id',
            'lines.*.level_id' => 'required|exists:levels,id',
            'lines.*.location_type' => 'nullable|in:Sale,Warehouse,S,W',
            'lines.*.side' => 'nullable|string|in:F,B,Natural,None',
            'lines.*.location_name' => 'nullable|string|max:255',
            'lines.*.branch_short_name' => 'nullable|string|max:20',
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => 'error', 'message' => $validate->errors()], 422);
        }

        try {
            $result = $this->persistLocationDocument($request);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Location document store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save location request. Please try again.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Location document store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save location request. Please try again.',
            ], 500);
        }

        return $result;
    }

    private function persistLocationDocument(Request $request)
    {
        $authUser = getAuthUser();
        $lineBranchIds = collect($request->lines)->pluck('branch_id')->unique()->filter();
        $headerBranch = $lineBranchIds->count() === 1
            ? Branch::find($lineBranchIds->first())
            : null;
        $headerBranch = $headerBranch ?: Branch::find($authUser->branch_id);
        $headerShort = $headerBranch?->branch_short_name ?: 'XX';

        $preparedLines = [];
        $seenNames = [];

        foreach ($request->lines as $index => $line) {
            $branch = Branch::findOrFail($line['branch_id']);
            $zone = $this->resolveZoneFromLine($line);
            $row = Row::findOrFail($line['row_id']);
            $bay = Bay::findOrFail($line['bay_id']);
            $level = Level::findOrFail($line['level_id']);

            $side_name = '';
            if (!empty($line['side']) && !in_array($line['side'], ['Natural', 'None'], true)) {
                $side_name = $line['side'];
            }

            $locationType = $line['location_type'] ?? null;
            $isSale = in_array($locationType, ['Sale', 'S'], true)
                ? true
                : (in_array($locationType, ['Warehouse', 'W'], true)
                    ? false
                    : $authUser->getRoleNames()->contains('Sale'));

            $shortName = $line['branch_short_name'] ?? $branch->branch_short_name;
            $locationName = trim((string) ($line['location_name'] ?? ''));
            if ($locationName === '' || str_contains($locationName, '?')) {
                $locationName = $this->buildLocationName(
                    $branch,
                    $zone,
                    $row,
                    $bay,
                    $level,
                    $isSale,
                    $side_name,
                    $shortName
                );
            }

            if (isset($seenNames[$locationName])) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Duplicate location code in form: {$locationName}",
                    'line' => $index + 1,
                ], 422);
            }
            $seenNames[$locationName] = true;

            if (Location::where('location_name', $locationName)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Location code already exists in locations: {$locationName}",
                    'line' => $index + 1,
                    'duplicate_in' => 'locations',
                ], 409);
            }

            if (
                LocationRequest::where('location_name', $locationName)
                    ->where('status', 'request')
                    ->exists()
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Pending request already exists for: {$locationName}",
                    'line' => $index + 1,
                    'duplicate_in' => 'location_requests',
                ], 409);
            }

            $preparedLines[] = [
                'branch_id' => $branch->id,
                'location_category' => $line['location_category'],
                'location_type' => $isSale ? 'S' : 'W',
                'zone_id' => $zone->id,
                'row_id' => $row->id,
                'bay_id' => $bay->id,
                'level_id' => $level->id,
                'side' => $line['side'] ?? null,
                'branch_short_name' => $shortName,
                'location_name' => $locationName,
            ];
        }

        $document = DB::transaction(function () use ($preparedLines, $authUser, $headerBranch, $headerShort) {
            $documentNumber = $this->generateDocumentNumber($headerShort);

            $document = LocationRequestDocument::create([
                'document_number' => $documentNumber,
                'user_id' => $authUser->id,
                'branch_id' => $headerBranch?->id,
                'branch_short_name' => $headerShort,
                'status' => 'request',
            ]);

            foreach ($preparedLines as $line) {
                LocationRequest::create(array_merge($line, [
                    'document_id' => $document->id,
                    'user_id' => $authUser->id,
                    'status' => 'request',
                ]));
            }

            $approver = User::where('emp_id', self::LOCATION_APPROVER_EMP_ID)->first();
            if ($approver) {
                LocationRequestNotification::create([
                    'document_id' => $document->id,
                    'location_request_id' => null,
                    'user_id' => $approver->id,
                    'message' => "New location request: {$documentNumber}",
                ]);
            }

            return $document->load(['lines.branch', 'user:id,name,emp_id']);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Location request document submitted successfully!',
            'data' => $document,
        ]);
    }

    private function generateDocumentNumber(string $branchShortName): string
    {
        $date = now()->format('Ymd');
        $prefix = 'LR' . $branchShortName . $date . '-';

        $latest = LocationRequestDocument::where('document_number', 'like', $prefix . '%')
            ->orderByDesc('document_number')
            ->lockForUpdate()
            ->value('document_number');

        $seq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function indexDocuments(Request $request)
    {
        $query = LocationRequestDocument::with([
            'user:id,name,emp_id',
            'branch:id,branch_name,branch_short_name',
        ])
            ->withCount('lines')
            ->orderByDesc('created_at');

        if (!$this->isLocationApprover()) {
            $user = getAuthUser();
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('lines', function ($lineQuery) use ($user) {
                        $lineQuery->where('branch_id', $user->branch_id);
                    });
            });
        }

        if ($this->isLocationApprover() && $request->filled('branch_id')) {
            $branchId = $request->branch_id;
            if ($branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('document_number', 'ILIKE', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'ILIKE', "%{$search}%")
                                ->orWhere('emp_id', 'ILIKE', "%{$search}%");
                        });
                });
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', Carbon::parse($request->to_date)->endOfDay());
        }

        $documents = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $documents,
        ]);
    }

    public function showDocument($id)
    {
        $query = LocationRequestDocument::with([
            'user:id,name,emp_id',
            'branch:id,branch_name,branch_short_name',
            'lines.user:id,name,emp_id',
            'lines.branch:id,branch_name,branch_short_name',
            'lines.zone:id,name',
            'lines.row:id,name',
            'lines.bay:id,name',
            'lines.level:id,name',
        ]);

        if (!$this->isLocationApprover()) {
            $user = getAuthUser();
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('lines', function ($lineQuery) use ($user) {
                        $lineQuery->where('branch_id', $user->branch_id);
                    });
            });
        }

        $document = $query->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $document,
        ]);
    }

    public function destroyDocumentLine($id, $lineId)
    {
        $authUser = getAuthUser();
        $document = LocationRequestDocument::findOrFail($id);

        if (!$this->isLocationApprover($authUser)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete lines on this document.',
            ], 403);
        }

        if ($document->status !== 'request') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending documents can be edited.',
            ], 409);
        }

        $line = LocationRequest::where('document_id', $document->id)
            ->where('id', $lineId)
            ->firstOrFail();

        $remainingCount = 0;
        $documentDeleted = false;

        DB::transaction(function () use ($document, $line, &$remainingCount, &$documentDeleted) {
            LocationRequestNotification::where('location_request_id', $line->id)->delete();
            $line->delete();

            $remainingCount = LocationRequest::where('document_id', $document->id)->count();

            if ($remainingCount === 0) {
                LocationRequestNotification::where('document_id', $document->id)->delete();
                $document->delete();
                $documentDeleted = true;
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => $documentDeleted
                ? 'Line deleted. Document removed because it had no remaining lines.'
                : 'Line deleted successfully.',
            'data' => [
                'document_deleted' => $documentDeleted,
                'remaining_lines' => $remainingCount,
            ],
        ]);
    }

    public function updateDocumentLine(Request $request, $id, $lineId)
    {
        if (!$this->isLocationApprover()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to edit lines on this document.',
            ], 403);
        }

        $document = LocationRequestDocument::findOrFail($id);
        if ($document->status !== 'request') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending documents can be edited.',
            ], 409);
        }

        $line = LocationRequest::where('document_id', $document->id)
            ->where('id', $lineId)
            ->firstOrFail();

        $validate = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'location_category' => 'required|string|max:255',
            'zone_id' => 'required_without:zone|nullable|exists:zones,id',
            'zone' => 'required_without:zone_id|nullable|string|max:20',
            'row_id' => 'required|exists:rows,id',
            'bay_id' => 'required|exists:bays,id',
            'level_id' => 'required|exists:levels,id',
            'location_type' => 'nullable|in:Sale,Warehouse,S,W',
            'side' => 'nullable|string|in:F,B,Natural,None',
            'branch_short_name' => 'nullable|string|max:20',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please complete all required fields.',
            ], 422);
        }

        try {
            $branch = Branch::findOrFail($request->branch_id);
            $zone = $this->resolveZoneFromLine($request->all());
            $row = Row::findOrFail($request->row_id);
            $bay = Bay::findOrFail($request->bay_id);
            $level = Level::findOrFail($request->level_id);

            $sideName = '';
            if ($request->filled('side') && !in_array($request->side, ['Natural', 'None'], true)) {
                $sideName = $request->side;
            }

            $locationType = $request->input('location_type');
            $isSale = in_array($locationType, ['Sale', 'S'], true)
                ? true
                : (in_array($locationType, ['Warehouse', 'W'], true)
                    ? false
                    : ($request->location_category !== 'RG_WAREHOUSE'));

            $shortName = $request->branch_short_name ?: $branch->branch_short_name;
            $locationName = $this->buildLocationName(
                $branch,
                $zone,
                $row,
                $bay,
                $level,
                $isSale,
                $sideName,
                $shortName
            );

            $duplicate = Location::where('location_name', $locationName)->exists()
                || LocationRequest::where('location_name', $locationName)
                    ->where('status', 'request')
                    ->where('id', '!=', $line->id)
                    ->exists();

            if ($duplicate) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Location code already exists: {$locationName}",
                    'duplicate_in' => 'locations',
                ], 409);
            }

            $line->update([
                'branch_id' => $branch->id,
                'location_category' => $request->location_category,
                'location_type' => $isSale ? 'S' : 'W',
                'zone_id' => $zone->id,
                'row_id' => $row->id,
                'bay_id' => $bay->id,
                'level_id' => $level->id,
                'side' => $request->side,
                'branch_short_name' => $shortName,
                'location_name' => $locationName,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Location line update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update location line. Please try again.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Line updated successfully.',
            'data' => $line->fresh([
                'branch:id,branch_name,branch_short_name',
                'zone:id,name',
                'row:id,name',
                'bay:id,name',
                'level:id,name',
            ]),
        ]);
    }

    public function approveDocument($id)
    {
        if (!$this->isLocationApprover()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to approve location requests.',
            ], 403);
        }

        $document = LocationRequestDocument::with('lines')->findOrFail($id);

        if ($document->status !== 'request') {
            return response()->json([
                'status' => 'error',
                'message' => 'This document has already been processed.',
            ], 409);
        }

        $lines = $document->lines()->where('status', 'request')->get();
        if ($lines->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No pending lines found on this document.',
            ], 409);
        }

        foreach ($lines as $line) {
            if (Location::where('location_name', $line->location_name)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Location already exists: {$line->location_name}",
                ], 409);
            }

            $branch = Branch::findOrFail($line->branch_id);
            if (
                LocationHd::where('location_code', $line->location_name)
                    ->where('branch_code', $branch->branch_code)
                    ->exists()
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Location already exists in location_hd: {$line->location_name}",
                ], 409);
            }
        }

        $approvedAt = now();
        $createdHdIds = [];

        try {
            foreach ($lines as $line) {
                $branch = Branch::findOrFail($line->branch_id);
                $createdHdIds[] = $this->insertLocationHd($line, $branch->branch_code, $approvedAt);
            }

            DB::transaction(function () use ($document, $lines) {
                foreach ($lines as $line) {
                    Location::create([
                        'location_name' => $line->location_name,
                        'branch_id' => $line->branch_id,
                    ]);
                    $line->update(['status' => 'completed']);
                }

                $document->update(['status' => 'completed']);

                LocationRequestNotification::where('document_id', $document->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
            });
        } catch (\Throwable $e) {
            if (!empty($createdHdIds)) {
                LocationHd::whereIn('location_id', $createdHdIds)->delete();
            }

            Log::error('Location document approve failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve location document.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Location document approved successfully.',
        ]);
    }

    public function rejectDocument(Request $request, $id)
    {
        if (!$this->isLocationApprover()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to reject location requests.',
            ], 403);
        }

        $validate = Validator::make($request->all(), [
            'remark' => 'required|string|max:1000',
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => 'error', 'message' => $validate->errors()]);
        }

        $document = LocationRequestDocument::with('lines')->findOrFail($id);

        if ($document->status !== 'request') {
            return response()->json([
                'status' => 'error',
                'message' => 'This document has already been processed.',
            ], 409);
        }

        DB::transaction(function () use ($document, $request) {
            $document->update([
                'status' => 'cancel',
                'remark' => $request->remark,
            ]);

            LocationRequest::where('document_id', $document->id)
                ->where('status', 'request')
                ->update([
                    'status' => 'cancel',
                    'remark' => $request->remark,
                ]);

            LocationRequestNotification::where('document_id', $document->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Location document rejected.',
        ]);
    }

    private function resolveZoneFromLine(array $line): Zone
    {
        $name = strtoupper(trim((string) ($line['zone'] ?? '')));
        if ($name !== '' && $name !== '?') {
            $zone = Zone::whereRaw('upper(name) = ?', [$name])->first();
            if ($zone) {
                return $zone;
            }

            $this->syncPostgresIdSequence('zones');

            return Zone::create(['name' => $name]);
        }

        return Zone::findOrFail($line['zone_id']);
    }

    private function syncPostgresIdSequence(string $table): void
    {
        $allowed = ['zones' => 'zones'];
        if (!isset($allowed[$table])) {
            return;
        }

        $safe = $allowed[$table];

        try {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('{$safe}', 'id'), COALESCE((SELECT MAX(id) FROM {$safe}), 1))"
            );
        } catch (\Throwable $e) {
            Log::warning('Could not sync ID sequence', [
                'table' => $table,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildLocationName($branch, $zone, $row, $bay, $level, $isSale, $side_name, $shortName = null)
    {
        $short = $shortName ?: $branch->branch_short_name;
        if ($isSale) {
            if ($side_name !== '') {
                return "{$short}S_{$zone->name}_{$row->name}_{$side_name}_{$bay->name}_{$level->name}";
            }

            return "{$short}S_{$zone->name}_{$row->name}_{$bay->name}_{$level->name}";
        }

        return "{$short}W_{$zone->name}_{$row->name}_{$bay->name}_{$level->name}";
    }

    public function checkExists(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'location_name' => 'required|string|max:255',
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => 'error', 'message' => $validate->errors()]);
        }

        $locationName = $request->location_name;
        $inLocations = Location::where('location_name', $locationName)->exists();
        $inRequests = LocationRequest::where('location_name', $locationName)
            ->where('status', 'request')
            ->exists();

        return response()->json([
            'status' => 'success',
            'exists' => $inLocations || $inRequests,
            'in_locations' => $inLocations,
            'in_requests' => $inRequests,
        ]);
    }

    public function bulkCheckExists(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'location_names' => 'required|array|min:1',
            'location_names.*' => 'required|string|max:255',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validate->errors(),
            ], 422);
        }

        $names = collect($request->input('location_names', []))
            ->filter(fn ($n) => is_string($n) && trim($n) !== '')
            ->map(fn ($n) => trim($n))
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No location names provided.',
            ], 422);
        }

        $inLocations = Location::whereIn('location_name', $names)
            ->pluck('location_name')
            ->unique()
            ->values();

        $inRequests = LocationRequest::whereIn('location_name', $names)
            ->where('status', 'request')
            ->pluck('location_name')
            ->unique()
            ->values();

        $exists = $inLocations
            ->merge($inRequests)
            ->unique()
            ->values();

        return response()->json([
            'status' => 'success',
            'exists' => $exists,
            'in_locations' => $inLocations,
            'in_requests' => $inRequests,
        ]);
    }

    public function indexRequests(Request $request)
    {
        $userBranchIds = getAuthUser()->branch_id;

        $query = LocationRequest::with([
            'user:id,name,emp_id',
            'branch:id,branch_name,branch_short_name',
        ])
            ->where('branch_id', $userBranchIds)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    public function showRequest($id)
    {
        $query = LocationRequest::with([
            'user:id,name,emp_id',
            'branch:id,branch_name,branch_short_name',
            'zone:id,name',
            'row:id,name',
            'bay:id,name',
            'level:id,name',
        ]);

        if (!$this->isLocationApprover()) {
            $query->where('branch_id', getAuthUser()->branch_id);
        }

        $locationRequest = $query->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $locationRequest,
        ]);
    }

    public function approveRequest($id)
    {
        if (!$this->isLocationApprover()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to approve location requests.',
            ], 403);
        }

        $locationRequest = LocationRequest::findOrFail($id);

        if ($locationRequest->status !== 'request') {
            return response()->json([
                'status' => 'error',
                'message' => 'This location request has already been processed.',
            ], 409);
        }

        if (Location::where('location_name', $locationRequest->location_name)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This location code already exists in locations.',
            ], 409);
        }

        $branch = Branch::findOrFail($locationRequest->branch_id);
        $branchCode = $branch->branch_code;

        if (
            LocationHd::where('location_code', $locationRequest->location_name)
                ->where('branch_code', $branchCode)
                ->exists()
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'This location code already exists in location_hd.',
            ], 409);
        }

        $approvedAt = now();
        $locationHdId = null;

        try {
            $locationHdId = $this->insertLocationHd($locationRequest, $branchCode, $approvedAt);

            DB::transaction(function () use ($locationRequest) {
                Location::create([
                    'location_name' => $locationRequest->location_name,
                    'branch_id' => $locationRequest->branch_id,
                ]);

                $locationRequest->update(['status' => 'completed']);

                LocationRequestNotification::where('location_request_id', $locationRequest->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
            });
        } catch (\Throwable $e) {
            if ($locationHdId) {
                LocationHd::where('location_id', $locationHdId)->delete();
            }

            Log::error('Location approve failed', [
                'location_request_id' => $locationRequest->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve location request.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Location request approved and added to locations.',
        ]);
    }

    private function insertLocationHd(LocationRequest $locationRequest, string $branchCode, $approvedAt): int
    {
        $typeCode = $this->normalizeLocationTypeCode($locationRequest->location_type);
        $nextId = ((int) LocationHd::max('location_id')) + 1;

        LocationHd::create([
            'location_id' => $nextId,
            'location_code' => $locationRequest->location_name,
            'location_date' => $approvedAt,
            'branch_code' => $branchCode,
            'location_status_code' => 'N',
            'location_type_sub_code' => $typeCode,
            'location_bank_code' => null,
            'location_remark' => null,
            'location_shelf_id' => $locationRequest->row_id,
            'location_bay_id' => $locationRequest->bay_id,
            'location_empsave' => 'AUTO',
            'savetime' => $approvedAt,
            'category_code' => null,
            'type_code' => null,
            'flag_new' => false,
            'code_new' => null,
            'level_qty' => null,
            'update_time' => $approvedAt,
            'local_short_name' => null,
            'group_code' => null,
        ]);

        return $nextId;
    }

    private function normalizeLocationTypeCode(?string $locationType): string
    {
        if (in_array($locationType, ['S', 'Sale'], true)) {
            return 'S';
        }

        if (in_array($locationType, ['W', 'Warehouse'], true)) {
            return 'W';
        }

        return $locationType ?: 'W';
    }

    public function rejectRequest(Request $request, $id)
    {
        if (!$this->isLocationApprover()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to reject location requests.',
            ], 403);
        }

        $validate = Validator::make($request->all(), [
            'remark' => 'required|string|max:1000',
        ]);

        if ($validate->fails()) {
            return response()->json(['status' => 'error', 'message' => $validate->errors()]);
        }

        $locationRequest = LocationRequest::findOrFail($id);

        if ($locationRequest->status !== 'request') {
            return response()->json([
                'status' => 'error',
                'message' => 'This location request has already been processed.',
            ], 409);
        }

        DB::transaction(function () use ($locationRequest, $request) {
            $locationRequest->update([
                'status' => 'cancel',
                'remark' => $request->remark,
            ]);

            LocationRequestNotification::where('location_request_id', $locationRequest->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Location request rejected.',
        ]);
    }

    public function indexNotifications()
    {
        $notifications = LocationRequestNotification::with([
            'document:id,document_number,status,user_id',
            'document.user:id,name,emp_id',
            'locationRequest:id,user_id,location_name,status,branch_id,document_id',
            'locationRequest.user:id,name,emp_id',
        ])
            ->where('user_id', auth()->id())
            ->where(function ($q) {
                $q->whereHas('document', function ($doc) {
                    $doc->where('status', 'request');
                })->orWhere(function ($legacy) {
                    $legacy->whereNull('document_id')
                        ->whereHas('locationRequest', function ($req) {
                            $req->where('status', 'request');
                        });
                });
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
            'unread_count' => $notifications->count(),
        ]);
    }

    public function markNotificationRead($id)
    {
        $notification = LocationRequestNotification::where('user_id', auth()->id())
            ->findOrFail($id);

        $notification->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.',
        ]);
    }


    public function showAll(Request $request)
    {
        $authUser = getAuthUser();
        $userBranchIds = $authUser->branch_id;
        $roles = $authUser->getRoleNames();
        $isLocationApprover = $authUser->emp_id === self::LOCATION_APPROVER_EMP_ID;

        $documentSearch = trim((string) $request->input('document_number', ''));
        if ($documentSearch !== '') {
            return $this->showAllByDocumentNumber(
                $request,
                $authUser,
                $userBranchIds,
                $roles,
                $isLocationApprover,
                $documentSearch
            );
        }

        if ($isLocationApprover && $request->filled('branch_id')) {
            if ($request->branch_id === 'all') {
                $query = Location::query();
            } else {
                $query = Location::where('branch_id', $request->branch_id);
            }
        } else {
            $query = Location::where('branch_id', $userBranchIds);
        }

        if ($request->filled('zone')) {
            $zone = strtoupper($request->zone);
            $query->whereRaw("(string_to_array(location_name, '_'))[2] = ?", [$zone]);
        }

        if ($request->filled('row')) {
            $query->whereRaw("(string_to_array(location_name, '_'))[3] = ?", [$request->row]);
        }

        if ($request->filled('bay')) {
            $bay = $request->bay;
            // Warehouse names have 5 segments (bay at [4]); Sale names have 6 (side at [4], bay at [5])
            $query->where(function ($q) use ($bay) {
                $q->whereRaw(
                    "((array_length(string_to_array(location_name, '_'), 1) = 5 AND (string_to_array(location_name, '_'))[4] = ?)
                      OR (array_length(string_to_array(location_name, '_'), 1) = 6 AND (string_to_array(location_name, '_'))[5] = ?))",
                    [$bay, $bay]
                );
            });
        }

        if ($request->filled('level')) {
            $query->whereRaw(
                "(string_to_array(location_name, '_'))[array_length(string_to_array(location_name, '_'), 1)] = ?",
                [$request->level]
            );
        }

        if ($request->filled('side')) {
            $query->whereRaw(
                "array_length(string_to_array(location_name, '_'), 1) = 6 AND (string_to_array(location_name, '_'))[4] = ?",
                [strtoupper($request->side)]
            );
        }

        if (! $this->applyLocationTypeRoleFilter($query, $roles, $userBranchIds, $isLocationApprover)) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        }

        $locations = $query->orderBy('created_at')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $locations,
        ]);
    }

    private function showAllByDocumentNumber(
        Request $request,
        $authUser,
        $userBranchIds,
        $roles,
        bool $isLocationApprover,
        string $documentSearch
    ) {
        if (
            ! $roles->contains('Sale') &&
            ! $roles->contains('Warehouse') &&
            ! $roles->contains('Branch Manager') &&
            ! $roles->contains('Operation Analystis') &&
            ! $isLocationApprover
        ) {
            return response()->json([
                'status' => 'success',
                'document_match' => false,
                'data' => [
                    'data' => [],
                    'current_page' => 1,
                    'total' => 0,
                    'per_page' => 20,
                    'last_page' => 1,
                ],
            ]);
        }

        $docQuery = LocationRequestDocument::query()
            ->where('document_number', 'ILIKE', '%'.$documentSearch.'%');

        if (! $isLocationApprover) {
            $docQuery->where(function ($q) use ($authUser) {
                $q->where('user_id', $authUser->id)
                    ->orWhereHas('lines', function ($lineQuery) use ($authUser) {
                        $lineQuery->where('branch_id', $authUser->branch_id);
                    });
            });
        }

        $documents = $docQuery->orderByDesc('created_at')->get(['id', 'document_number', 'status']);

        if ($documents->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'document_match' => false,
                'data' => [
                    'data' => [],
                    'current_page' => 1,
                    'total' => 0,
                    'per_page' => 20,
                    'last_page' => 1,
                ],
            ]);
        }

        $lineQuery = LocationRequest::query()
            ->whereIn('document_id', $documents->pluck('id'))
            ->orderBy('location_name');

        if ($isLocationApprover && $request->filled('branch_id') && $request->branch_id !== 'all') {
            $lineQuery->where('branch_id', $request->branch_id);
        } elseif (! $isLocationApprover) {
            $lineQuery->where('branch_id', $userBranchIds);
        }

        $lines = $lineQuery->get(['id', 'location_name', 'branch_id', 'status']);

        $lines = $lines->filter(function ($line) use ($roles, $userBranchIds, $isLocationApprover, $request) {
            return $this->locationNameAllowedForRoles(
                $line->location_name,
                $roles,
                $userBranchIds,
                $isLocationApprover
            ) && $this->locationNameMatchesSegmentFilters($line->location_name, $request);
        })->values();

        if ($lines->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'document_match' => true,
                'document_numbers' => $documents->pluck('document_number')->values(),
                'data' => [
                    'data' => [],
                    'current_page' => 1,
                    'total' => 0,
                    'per_page' => 20,
                    'last_page' => 1,
                ],
            ]);
        }

        $names = $lines->pluck('location_name')->unique()->values();

        $locationQuery = Location::query()->whereIn('location_name', $names);
        if ($isLocationApprover && $request->filled('branch_id') && $request->branch_id !== 'all') {
            $locationQuery->where('branch_id', $request->branch_id);
        } elseif (! $isLocationApprover) {
            $locationQuery->where('branch_id', $userBranchIds);
        }

        $existing = $locationQuery->get()->keyBy('location_name');

        $merged = $lines->map(function ($line) use ($existing) {
            if ($existing->has($line->location_name)) {
                $loc = $existing->get($line->location_name);

                return [
                    'id' => $loc->id,
                    'location_name' => $loc->location_name,
                    'branch_id' => $loc->branch_id,
                    'pending' => false,
                    'created_at' => $loc->created_at,
                ];
            }

            return [
                'id' => - (int) $line->id,
                'location_name' => $line->location_name,
                'branch_id' => $line->branch_id,
                'pending' => true,
                'created_at' => null,
            ];
        })->unique('location_name')->values();

        $total = $merged->count();

        return response()->json([
            'status' => 'success',
            'document_match' => true,
            'document_numbers' => $documents->pluck('document_number')->values(),
            'data' => [
                'data' => $merged,
                'current_page' => 1,
                'total' => $total,
                'per_page' => $total > 0 ? $total : 20,
                'last_page' => 1,
            ],
        ]);
    }

    private function hasFullLocationTypeAccess($roles, bool $isLocationApprover): bool
    {
        return $roles->contains('Branch Manager')
            || $roles->contains('Operation Analystis')
            || $isLocationApprover;
    }

    private function allowedLocationTypeSuffixes($roles, $userBranchIds): ?array
    {
        $suffixes = [];

        if ($roles->contains('Sale')) {
            $suffixes[] = 'S';
        }

        if ($roles->contains('Warehouse')) {
            if (in_array($userBranchIds, [15, 16, 17], true)) {
                return null;
            }

            $suffixes[] = 'W';
            $suffixes[] = 'D';
        }

        if ($suffixes === []) {
            return [];
        }

        return array_values(array_unique($suffixes));
    }

    private function applyLocationTypeRoleFilter($query, $roles, $userBranchIds, bool $isLocationApprover): bool
    {
        if ($this->hasFullLocationTypeAccess($roles, $isLocationApprover)) {
            return true;
        }

        $suffixes = $this->allowedLocationTypeSuffixes($roles, $userBranchIds);

        if ($suffixes === null) {
            return true;
        }

        if ($suffixes === []) {
            return false;
        }

        $query->where(function ($q) use ($suffixes) {
            foreach ($suffixes as $suffix) {
                $q->orWhereRaw("right((string_to_array(location_name, '_'))[1], 1) = ?", [$suffix]);
            }
        });

        return true;
    }

    private function locationNameAllowedForRoles(
        string $locationName,
        $roles,
        $userBranchIds,
        bool $isLocationApprover
    ): bool {
        if ($this->hasFullLocationTypeAccess($roles, $isLocationApprover)) {
            return true;
        }

        $suffixes = $this->allowedLocationTypeSuffixes($roles, $userBranchIds);

        if ($suffixes === null) {
            return true;
        }

        if ($suffixes === []) {
            return false;
        }

        $parts = explode('_', $locationName);
        $prefix = $parts[0] ?? '';
        $lastChar = substr($prefix, -1);

        return in_array($lastChar, $suffixes, true);
    }

    private function locationNameMatchesSegmentFilters(string $locationName, Request $request): bool
    {
        $parts = explode('_', $locationName);
        $segmentCount = count($parts);

        if ($request->filled('zone')) {
            if (strtoupper($parts[1] ?? '') !== strtoupper($request->zone)) {
                return false;
            }
        }

        if ($request->filled('row')) {
            if (($parts[2] ?? '') !== $request->row) {
                return false;
            }
        }

        if ($request->filled('side')) {
            if ($segmentCount !== 6 || strtoupper($parts[3] ?? '') !== strtoupper($request->side)) {
                return false;
            }
        }

        if ($request->filled('bay')) {
            $bayIndex = $segmentCount === 6 ? 4 : 3;
            if (($parts[$bayIndex] ?? '') !== $request->bay) {
                return false;
            }
        }

        if ($request->filled('level')) {
            if (($parts[$segmentCount - 1] ?? '') !== $request->level) {
                return false;
            }
        }

        return true;
    }

    public function destroy($id)
    {
        if (!$this->isLocationApprover()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete locations.',
            ], 403);
        }

        $location = Location::findOrFail($id);

        $stockUsages = StockTracking::where('location_name', $location->location_name)
            ->select('id', 'product_code', 'product_name', 'total_qty')
            ->limit(5)
            ->get();

        if ($stockUsages->isNotEmpty()) {
            $productCodes = $stockUsages->pluck('product_code')->filter()->unique()->values()->all();
            $moreCount = StockTracking::where('location_name', $location->location_name)->count();

            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete this location because it is connected with product code(s) in stock trackings.',
                'linked_products' => $productCodes,
                'linked_count' => $moreCount,
            ], 409);
        }

        try {
            LocationHd::where('location_code', $location->location_name)->delete();
            $location->delete();
        } catch (\Throwable $e) {
            Log::error('Location delete failed', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete location.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Location deleted successfully.',
        ]);
    }

    public function destroyMany(Request $request)
    {
        if (!$this->isLocationApprover()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete locations.',
            ], 403);
        }

        $ids = collect($request->input('ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No locations selected.',
            ], 422);
        }

        $locations = Location::whereIn('id', $ids)->get();
        $deleted = [];
        $blocked = [];

        foreach ($locations as $location) {
            $stockUsages = StockTracking::where('location_name', $location->location_name)
                ->select('product_code')
                ->limit(5)
                ->get();

            if ($stockUsages->isNotEmpty()) {
                $blocked[] = [
                    'id' => $location->id,
                    'location_name' => $location->location_name,
                    'linked_products' => $stockUsages->pluck('product_code')->filter()->unique()->values()->all(),
                    'linked_count' => StockTracking::where('location_name', $location->location_name)->count(),
                ];
                continue;
            }

            try {
                LocationHd::where('location_code', $location->location_name)->delete();
                $location->delete();
                $deleted[] = $location->location_name;
            } catch (\Throwable $e) {
                Log::error('Location bulk delete failed', [
                    'location_id' => $location->id,
                    'error' => $e->getMessage(),
                ]);
                $blocked[] = [
                    'id' => $location->id,
                    'location_name' => $location->location_name,
                    'linked_products' => [],
                    'linked_count' => 0,
                    'message' => 'Failed to delete location.',
                ];
            }
        }

        $deletedCount = count($deleted);
        $blockedCount = count($blocked);

        if ($deletedCount === 0 && $blockedCount > 0) {
            $firstBlocked = $blocked[0];

            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete the selected locations because they are connected with product code(s).',
                'deleted_count' => 0,
                'blocked_count' => $blockedCount,
                'blocked' => $blocked,
                'locationName' => $firstBlocked['location_name'] ?? '',
                'linked_products' => $firstBlocked['linked_products'] ?? [],
                'linked_count' => $firstBlocked['linked_count'] ?? 0,
            ], 409);
        }

        return response()->json([
            'status' => 'success',
            'message' => $blockedCount > 0
                ? "{$deletedCount} location(s) deleted. {$blockedCount} skipped because they have stock."
                : "{$deletedCount} location(s) deleted successfully.",
            'deleted_count' => $deletedCount,
            'blocked_count' => $blockedCount,
            'blocked' => $blocked,
        ]);
    }
}
