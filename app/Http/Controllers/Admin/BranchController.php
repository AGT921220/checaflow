<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Features\Branches\Application\CreateBranch;
use App\Features\Branches\Application\SearchBranches;
use App\Features\Branches\Application\UpdateBranch;
use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\CreateBranchData;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Branches\Domain\UpdateBranchData;
use App\Features\Shared\Domain\GeoPoint;
use App\Features\Users\Domain\UserRole;
use App\Http\Auth\RequestPageQuery;
use App\Http\Auth\TenantCompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BranchController extends Controller
{
    public function page(Request $request, TenantCompanyScope $scope): View
    {
        $isPlatform = $scope->isPlatform($request);

        return view('admin.branches.index', [
            'title' => 'Sucursales',
            'isPlatform' => $isPlatform,
            'canManage' => ! $isPlatform && $request->user()?->role === 'company_admin',
            'openModal' => old('_form'),
            'mapsApiKey' => (string) config('services.google.maps_key', ''),
            'companies' => $isPlatform ? $this->companies() : [],
        ]);
    }

    public function index(
        Request $request,
        SearchBranches $search,
        TenantCompanyScope $scope,
        RequestPageQuery $pages,
    ): JsonResponse {
        $companyId = $scope->optional($request);
        $result = $search->page(new SearchBranchesCriteria($companyId), $pages->from($request));
        /** @var list<Branch> $branches */
        $branches = $result->items;
        $companyNames = $this->companyNames($branches);
        $canManage = ! $scope->isPlatform($request) && $request->user()?->role === 'company_admin';

        $rows = [];

        foreach ($branches as $branch) {
            $location = $branch->location();
            $rows[] = [
                'id' => $branch->id(),
                'companyId' => $branch->companyId()->value,
                'companyName' => $companyNames[$branch->companyId()->value] ?? '—',
                'name' => $branch->name(),
                'address' => $branch->address(),
                'latitude' => $location?->latitude,
                'longitude' => $location?->longitude,
                'geofenceRadiusMeters' => $branch->geofenceRadiusMeters(),
                'requireGeofence' => $branch->requiresGeofence(),
                'isActive' => $branch->isActive(),
                'updateUrl' => $canManage ? route('admin.branches.update', $branch->id()) : null,
            ];
        }

        return response()->json([
            'data' => $rows,
            'meta' => $result->meta(),
        ]);
    }

    public function store(StoreBranchRequest $request, CreateBranch $create, TenantCompanyScope $scope): RedirectResponse
    {
        $companyId = $scope->forMutation($request);
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

        return redirect()->route('branches.page')->with('status', 'Sucursal creada.');
    }

    public function update(
        UpdateBranchRequest $request,
        int $branch,
        UpdateBranch $update,
        TenantCompanyScope $scope,
    ): RedirectResponse {
        $companyId = $scope->forMutation($request);
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

        return redirect()->route('branches.page')->with('status', 'Sucursal actualizada.');
    }

    private function location(StoreBranchRequest|UpdateBranchRequest $request): ?GeoPoint
    {
        if (! $request->filled('latitude') || ! $request->filled('longitude')) {
            return null;
        }

        return new GeoPoint((float) $request->input('latitude'), (float) $request->input('longitude'));
    }

    /**
     * @param  list<Branch>  $branches
     * @return array<int, string>
     */
    private function companyNames(array $branches): array
    {
        $ids = array_values(array_unique(array_map(
            fn (Branch $branch): int => $branch->companyId()->value,
            $branches,
        )));

        if ($ids === []) {
            return [];
        }

        return Company::query()->whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function companies(): array
    {
        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $company): array => [
                'id' => (int) $company->id,
                'name' => (string) $company->name,
            ])
            ->all();
    }
}
