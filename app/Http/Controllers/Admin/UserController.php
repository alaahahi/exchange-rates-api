<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Admin\ManageUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly ManageUserService $users,
    ) {}

    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->orderBy('id')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());

        return back()->with('success', 'تم إضافة المستخدم بنجاح.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated());

        return back()->with('success', 'تم تحديث بيانات المستخدم.');
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $this->users->updatePassword($user, $request->validated());

        return back()->with('success', 'تم تحديث كلمة المرور.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->users->delete($user);

        return back()->with('success', 'تم حذف المستخدم.');
    }
}
