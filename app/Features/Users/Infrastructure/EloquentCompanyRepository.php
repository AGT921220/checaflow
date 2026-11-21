<?php

declare(strict_types=1);

namespace App\Features\Users\Infrastructure;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\Exceptions\CompanyIsNotPersisted;
use App\Features\Users\Domain\Exceptions\SlugAlreadyUsed;
use App\Models\Company as CompanyRecord;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentCompanyRepository implements CompanyRepository
{
    public function find(CompanyId $companyId): ?Company
    {
        $record = CompanyRecord::query()->find($companyId->value);

        if ($record === null) {
            return null;
        }

        return $this->map($record);
    }

    public function slugExists(string $slug): bool
    {
        return CompanyRecord::query()
            ->withTrashed()
            ->where('slug', mb_strtolower(trim($slug)))
            ->exists();
    }

    public function save(Company $company): Company
    {
        $record = $this->record($company);
        $record->name = $company->name();
        $record->slug = $company->slug();
        $record->tax_id = $company->taxId();
        $record->plan_id = $company->planId();
        $record->plan_code = $company->planCode();
        $record->max_employees = $company->maxEmployees();
        $record->max_branches = $company->maxBranches();
        $record->is_active = $company->isActive();
        $record->timezone = $company->timezone();

        try {
            $record->save();
        } catch (UniqueConstraintViolationException) {
            throw new SlugAlreadyUsed;
        }

        return $this->map($record);
    }

    private function record(Company $company): CompanyRecord
    {
        try {
            $id = $company->id()->value;
        } catch (CompanyIsNotPersisted) {
            return new CompanyRecord;
        }

        $record = CompanyRecord::query()->find($id);

        if ($record === null) {
            throw new CompanyIsNotPersisted;
        }

        return $record;
    }

    private function map(CompanyRecord $record): Company
    {
        return Company::open(
            (string) $record->name,
            (string) $record->slug,
            $record->tax_id !== null ? (string) $record->tax_id : null,
            (int) $record->plan_id,
            (string) $record->plan_code,
            (int) $record->max_employees,
            $record->max_branches !== null ? (int) $record->max_branches : null,
            (bool) $record->is_active,
            (string) $record->timezone,
        )->withId((int) $record->id);
    }
}
