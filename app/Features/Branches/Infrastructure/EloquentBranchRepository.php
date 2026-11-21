<?php

declare(strict_types=1);

namespace App\Features\Branches\Infrastructure;

use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\Exceptions\BranchIsNotPersisted;
use App\Features\Branches\Domain\Exceptions\BranchNotFound;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\GeoPoint;
use App\Models\Branch as BranchRecord;

final class EloquentBranchRepository implements BranchRepository
{
    public function save(Branch $branch): Branch
    {
        $record = $this->record($branch);
        $record->company_id = $branch->companyId()->value;
        $record->name = $branch->name();
        $record->address = $branch->address();
        $record->latitude = $branch->location()?->latitude;
        $record->longitude = $branch->location()?->longitude;
        $record->geofence_radius_meters = $branch->geofenceRadiusMeters();
        $record->require_geofence = $branch->requiresGeofence();
        $record->is_active = $branch->isActive();
        $record->save();

        return $this->map($record);
    }

    public function find(CompanyId $companyId, int $branchId): ?Branch
    {
        $record = BranchRecord::query()
            ->where('company_id', $companyId->value)
            ->find($branchId);

        if ($record === null) {
            return null;
        }

        return $this->map($record);
    }

    public function countActiveByCompanyId(CompanyId $companyId): int
    {
        return BranchRecord::query()
            ->where('company_id', $companyId->value)
            ->where('is_active', true)
            ->count();
    }

    public function search(SearchBranchesCriteria $criteria): array
    {
        $query = BranchRecord::query()->where('company_id', $criteria->companyId->value);

        if ($criteria->isActive !== null) {
            $query->where('is_active', $criteria->isActive);
        }

        $query->orderBy($criteria->sort->column, $criteria->sort->direction);

        return array_values($query->get()->map(fn (BranchRecord $record): Branch => $this->map($record))->all());
    }

    private function record(Branch $branch): BranchRecord
    {
        $id = $this->persistedId($branch);

        if ($id === null) {
            return new BranchRecord;
        }

        $record = BranchRecord::query()
            ->where('company_id', $branch->companyId()->value)
            ->find($id);

        if ($record === null) {
            throw new BranchNotFound;
        }

        return $record;
    }

    private function persistedId(Branch $branch): ?int
    {
        try {
            return $branch->id();
        } catch (BranchIsNotPersisted) {
            return null;
        }
    }

    private function map(BranchRecord $record): Branch
    {
        return new Branch(
            (int) $record->id,
            new CompanyId((int) $record->company_id),
            $record->name,
            $record->address,
            $this->location($record->latitude, $record->longitude),
            $this->radius($record->geofence_radius_meters),
            (bool) $record->require_geofence,
            (bool) $record->is_active,
        );
    }

    private function location(mixed $latitude, mixed $longitude): ?GeoPoint
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return new GeoPoint((float) $latitude, (float) $longitude);
    }

    private function radius(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }
}
