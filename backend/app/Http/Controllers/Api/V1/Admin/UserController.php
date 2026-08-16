<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::with('roles:id,name,slug')->latest()->paginate(25);

        return response()->json($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $roleIds = $data['role_ids'] ?? [];
            unset($data['role_ids']);
            $user = User::create($data);
            if ($roleIds) $user->roles()->sync($roleIds);
            return $user->load('roles');
        });

        return response()->json(['data' => $user], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $user->load('roles.permissions')]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $roleIds = $data['role_ids'] ?? null;
        unset($data['role_ids']);

        if (array_key_exists('password', $data) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        DB::transaction(function () use ($user, $data, $roleIds) {
            $user->update($data);
            if ($roleIds !== null) $user->roles()->sync($roleIds);
        });

        return response()->json(['data' => $user->fresh()->load('roles')]);
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if(auth()->id() === $user->id, 422, 'You cannot deactivate your own account.');
        $user->update(['is_active' => false]);
        $user->delete();

        return response()->json(['message' => 'User deactivated.']);
    }
}
