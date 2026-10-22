<?php

namespace App\Services\Ums;

use App\Models\Branch;
use App\Models\User;
use App\Models\UserBranch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Pro1\UmsIntegration\Contracts\UserLifecycle;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WarehouseUserLifecycle implements UserLifecycle
{
    public function deactivate(string $empCode): array
    {
        return DB::transaction(function () use ($empCode) {
            $users = User::where('emp_id', $empCode)
                ->lockForUpdate()
                ->get();

            if ($users->isEmpty()) {
                throw new HttpException(404, 'User not found.');
            }

            $this->checkEmpDuplicate($users);

            $user = $users->first();

            $alreadyInactive = $user->status !== null
                && (int) $user->status === 0;

            if (!$alreadyInactive) {
                $user->status = 0;
                $user->save();
            }

            $user->tokens()->delete();

            return [
                'already_inactive' => $alreadyInactive,
                'emp_code' => $user->emp_id,
                'status' => 0,
            ];
        });
    }

    public function activate(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $users = User::where('emp_id', $data['emp_code'])
                ->lockForUpdate()
                ->get();

            $this->checkEmpDuplicate($users);

            $user = $users->first();
            $isNewUser = $user === null;

            $roles = Role::where('name', $data['role_name'])
                ->where('guard_name', 'web')
                ->get();

            if ($roles->isEmpty()) {
                throw new HttpException(404, 'Role not found.');
            }

            if ($roles->count() > 1) {
                throw new HttpException(409, 'Duplicate roles found.');
            }

            $branchIds = $this->get_branch_ids_by_branch_code(
                $data['branch_shortcodes']
            );

            if ($isNewUser) {
                if (empty($data['employee'])) {
                    throw new HttpException(
                        422,
                        'Employee information is required for a new user.'
                    );
                }

                $user = new User();
                $user->title = null;
                $user->name = $data['employee']['name'];
                $user->emp_id = $data['emp_code'];
                $user->password = Hash::make($data['emp_code']);
            }

            $user->branch_id = $branchIds[0];
            $user->all_branch = null;
            $user->status = 1;
            $user->save();

            $user->syncRoles([$roles->first()]);

            UserBranch::where('user_id', $user->id)->delete();

            foreach ($branchIds as $branchId) {
                UserBranch::create([
                    'user_id' => $user->id,
                    'branch_id' => $branchId,
                ]);
            }

            return [
                'created' => $isNewUser,
                'emp_code' => $user->emp_id,
                'status' => (int) $user->status,
            ];
        });
    }

    private function checkEmpDuplicate($users): void
    {
        if ($users->count() > 1) {
            throw new HttpException(
                409,
                'Duplicate employee accounts found.'
            );
        }
    }

    private function get_branch_ids_by_branch_code(array $branchCodes): array
    {
        $branchesByCode = Branch::whereIn('branch_code', $branchCodes)
            ->get()
            ->groupBy('branch_code');

        $branchIds = [];

        foreach ($branchCodes as $code) {
            $matches = $branchesByCode->get($code, collect());

            if ($matches->isEmpty()) {
                throw new HttpException(
                    422,
                    "Branch not found: {$code}"
                );
            }

            if ($matches->count() > 1) {
                throw new HttpException(
                    409,
                    "Duplicate branch code found: {$code}"
                );
            }

            $branchIds[] = (int) $matches->first()->id;
        }

        if ($branchIds === []) {
            throw new HttpException(422, 'At least one branch is required.');
        }

        return array_values(array_unique($branchIds));
    }
}