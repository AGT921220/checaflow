<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Features\Branches\Application\CreateBranch;
use App\Features\Branches\Application\SearchBranches;
use App\Features\Branches\Application\UpdateBranch;
use App\Features\Branches\Domain\CreateBranchData;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Branches\Domain\UpdateBranchData;
use App\Features\Shared\Domain\GeoPoint;
use App\Features\Users\Domain\UserRole;
use App\Http\Auth\CurrentCompanyId;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BranchController extends Controller
{
    public function index(Request $request, SearchBranches $search, CurrentCompanyId $company): View
    {
        return view('admin.branches.index', [
            'title' => 'Sucursales',
            'branches' => $search->handle(new SearchBranchesCriteria($company->from($request))),
            'mapsApiKey' => (string) config('services.google.maps_key', ''),
            'openModal' => old('_form'),
        ]);
    }

    public function store(StoreBranchRequest $request, CreateBranch $create, CurrentCompanyId $company): RedirectResponse
    {
        $companyId = $company->from($request);
        $role = $request->user()?->role;

        if (! is_string($role)) {
            abort(403);
        }

        $create->handle(new CreateBranchData(
            UserRole::from($role),
            $companyId,
            $companyId,
            $request->string('name')->toString(),
            $request->filled('address') ? $request->string('address')->toString() : null,
            $this->location($request),
            $request->filled('geofence_radius_meters') ? $request->integer('geofence_radius_meters') : null,
            $request->boolean('require_geofence'),
            $request->boolean('is_active'),
        ));

        return redirect()->route('admin.branches.index')->with('status', 'Sucursal creada.');
    }

    public function update(
        UpdateBranchRequest $request,
        int $branch,
        UpdateBranch $update,
        CurrentCompanyId $company,
    ): RedirectResponse {
        $companyId = $company->from($request);
        $role = $request->user()?->role;

        if (! is_string($role)) {
            abort(403);
        }

        $update->handle(new UpdateBranchData(
            UserRole::from($role),
            $companyId,
            $companyId,
            $branch,
            $request->string('name')->toString(),
            $request->filled('address') ? $request->string('address')->toString() : null,
            $this->location($request),
            $request->filled('geofence_radius_meters') ? $request->integer('geofence_radius_meters') : null,
            $request->boolean('require_geofence'),
            $request->boolean('is_active'),
        ));

        return redirect()->route('admin.branches.index')->with('status', 'Sucursal actualizada.');
    }

    private function location(StoreBranchRequest|UpdateBranchRequest $request): ?GeoPoint
    {
        if (! $request->filled('latitude') || ! $request->filled('longitude')) {
            return null;
        }

        return new GeoPoint((float) $request->input('latitude'), (float) $request->input('longitude'));
    }
}
