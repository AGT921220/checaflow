<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Users;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Application\CreateCompany;
use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\CreateCompanyData;
use App\Features\Users\Domain\Exceptions\CompanyIsNotPersisted;
use App\Features\Users\Domain\Exceptions\EmployeeLimitOutsidePlan;
use App\Features\Users\Domain\Exceptions\PermissionDenied;
use App\Features\Users\Domain\Exceptions\SlugAlreadyUsed;
use App\Features\Users\Domain\Plan;
use App\Features\Users\Domain\PlanRepository;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRole;
use PHPUnit\Framework\TestCase;

final class CreateCompanyTest extends TestCase
{
    public function test_super_admin_creates_a_company_inside_the_plan(): void
    {
        $companies = new RecordingCompanies;
        $action = new CreateCompany($companies, new MemoryPlans, new AuthorizeActor(new RolePermissions));

        $company = $action->handle(new CreateCompanyData(
            UserRole::SuperAdmin,
            'Taller Norte',
            'Taller-Norte',
            null,
            1,
            9,
            true,
            'America/Mexico_City',
        ));

        $this->assertSame('Taller Norte', $company->name());
        $this->assertSame('taller-norte', $company->slug());
        $this->assertSame(1, $company->planId());
        $this->assertSame('micro', $company->planCode());
        $this->assertSame(9, $company->maxEmployees());
        $this->assertSame(2, $company->maxBranches());
        $this->assertCount(1, $companies->saved);
    }

    public function test_a_limit_outside_the_plan_is_rejected(): void
    {
        $companies = new RecordingCompanies;
        $action = new CreateCompany($companies, new MemoryPlans, new AuthorizeActor(new RolePermissions));

        try {
            $action->handle(new CreateCompanyData(
                UserRole::SuperAdmin,
                'Taller Norte',
                'taller-norte',
                null,
                1,
                10,
                true,
                'America/Mexico_City',
            ));
            $this->fail('A limit outside the plan must not be saved.');
        } catch (EmployeeLimitOutsidePlan) {
            $this->assertSame([], $companies->saved);
        }
    }

    public function test_a_repeated_slug_is_rejected(): void
    {
        $companies = new RecordingCompanies;
        $companies->taken = ['taller-norte'];
        $action = new CreateCompany($companies, new MemoryPlans, new AuthorizeActor(new RolePermissions));

        try {
            $action->handle($this->command(UserRole::SuperAdmin));
            $this->fail('A repeated slug must not be saved.');
        } catch (SlugAlreadyUsed) {
            $this->assertSame([], $companies->saved);
        }
    }

    public function test_a_company_admin_cannot_create_a_company(): void
    {
        $companies = new RecordingCompanies;
        $action = new CreateCompany($companies, new MemoryPlans, new AuthorizeActor(new RolePermissions));

        try {
            $action->handle($this->command(UserRole::CompanyAdmin));
            $this->fail('A company admin must not create a company.');
        } catch (PermissionDenied) {
            $this->assertSame([], $companies->saved);
        }
    }

    private function command(UserRole $role): CreateCompanyData
    {
        return new CreateCompanyData(
            $role,
            'Taller Norte',
            'taller-norte',
            null,
            1,
            9,
            true,
            'America/Mexico_City',
        );
    }
}

final class RecordingCompanies implements CompanyRepository
{
    /** @var list<Company> */
    public array $saved = [];

    /** @var list<string> */
    public array $taken = [];

    public function find(CompanyId $companyId): ?Company
    {
        return null;
    }

    public function slugExists(string $slug): bool
    {
        return in_array($slug, $this->taken, true);
    }

    public function save(Company $company): Company
    {
        try {
            $company->id();
            $stored = $company;
        } catch (CompanyIsNotPersisted) {
            $stored = $company->withId(count($this->saved) + 1);
        }

        $this->saved[] = $stored;

        return $stored;
    }
}

final class MemoryPlans implements PlanRepository
{
    public function find(int $planId): ?Plan
    {
        return match ($planId) {
            1 => new Plan(1, 'micro', '1 a 9 empleados (Micro)', 1, 9, 'annual', 20, null, 2400, 'year'),
            2 => new Plan(2, 'small_business', '10 a 49 empleados (Pyme)', 10, 49, 'monthly_or_annual', 20, null, 200, 'month'),
            3 => new Plan(3, 'enterprise', '50+ empleados (Empresa)', 50, null, 'monthly_or_annual', 16, 18, 800, 'month'),
            default => null,
        };
    }

    public function all(): array
    {
        return array_values(array_filter([
            $this->find(1),
            $this->find(2),
            $this->find(3),
        ]));
    }
}
