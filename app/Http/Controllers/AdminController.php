<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AdminController extends Controller
{
    public function getUsers()
    {
        if (!Auth::check() || Auth::user()->role_id !== 1) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }
        $users = User::all();
        return response()->json($users, 200);
    }

    public function searchUser(Request $request)
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::exact('name'),
                AllowedFilter::partial('email'),
                AllowedFilter::exact('role_id'),
            ])
            ->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => __('messages.user_not_found')], 404);
        }

        return response()->json([
            'message' => __('messages.users_retrieved'), // Dil dosyasından mesaj al
            'data' => $users, // Kullanıcı listesi
        ], 200);    }

    public function createUser(Request $request)
    {
        if (!Auth::check() || Auth::user()->role_id !== 1) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('messages.operation_failed'),
                'error' => $validator->errors(),
            ], 400);
        }

        $validated = $validator->validated();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role_id' => $request->role_id,
        ]);

        return response()->json(['message' => __('messages.user_created'), 'user' => $user], 201);
    }

    public function updateUser(Request $request, $id)
    {
        if (!Auth::check() || Auth::user()->role_id !== 1) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $user = User::where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => __('messages.user_not_found')], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:6',
            'role_id' => 'sometimes|required|integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $validated = $validator->validated();

        $user->update([
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
            'role_id' => $request->role_id ?? $user->role_id,
        ]);

        return response()->json(['message' => __('messages.user_updated'), 'user' => $user], 200);
    }

    public function deleteUser($id)
    {
        if (!Auth::check() || Auth::user()->role_id !== 1) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $user = User::where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => __('messages.user_not_found')], 404);
        }

        $user->delete();

        return response()->json(['message' => __('messages.user_deleted')], 200);
    }
}