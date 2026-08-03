<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ManageUserService
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => now(),
            ]);

            Log::info('admin.user.created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'by' => auth()->id(),
            ]);

            return $user;
        });
    }

    /**
     * @param  array{name: string, email: string}  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
            ])->save();

            Log::info('admin.user.updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'by' => auth()->id(),
            ]);

            return $user->refresh();
        });
    }

    /**
     * @param  array{password: string}  $data
     */
    public function updatePassword(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->forceFill([
                'password' => $data['password'],
            ])->save();

            Log::info('admin.user.password_updated', [
                'user_id' => $user->id,
                'by' => auth()->id(),
            ]);

            return $user->refresh();
        });
    }

    public function delete(User $user): void
    {
        if ((int) $user->id === (int) auth()->id()) {
            throw ValidationException::withMessages([
                'user' => ['لا يمكنك حذف حسابك الحالي.'],
            ]);
        }

        DB::transaction(function () use ($user) {
            $userId = $user->id;
            $email = $user->email;

            $user->tokens()->delete();
            $user->delete();

            Log::info('admin.user.deleted', [
                'user_id' => $userId,
                'email' => $email,
                'by' => auth()->id(),
            ]);
        });
    }
}
