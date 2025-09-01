<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users with search, filtering, sorting, and pagination.
     * GET /api/users
     */
    public function index(Request $request)
    {
        $query = User::query();

        // 🔍 Search (name or email)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // 🎛 Dynamic filtering
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->input('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->input('created_to'));
        }

        // 🔽 Sorting
        if ($request->filled('sort_by')) {
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        // 📑 Pagination
        $users = $query->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total()
            ]
        ]);
    }

    /**
     * Store a newly created user.
     * POST /api/users
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6'
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json(new UserResource($user), 201);
    }

    /**
     * Display the specified user.
     * GET /api/users/{id}
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Update the specified user.
     * PUT /api/users/{id}
     * PATCH /api/users/{id}
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6'
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return new UserResource($user);
    }

    /**
     * Remove the specified user.
     * DELETE /api/users/{id}
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
