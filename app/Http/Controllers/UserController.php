<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        // Only owners can list all users
        $users = User::with('bank')->get();

        return response()->json([
            'data' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        // Authorize using policy
        $this->authorize('update', $user);

        // Store old values for audit log
        $oldValues = $user->toArray();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:admin,owner,customer',
            'no_hp' => 'sometimes|string|max:12',
            'no_hp2' => 'sometimes|string|max:12',
            'nama_no_hp2' => 'sometimes|string|max:255',
            'relasi_no_hp2' => 'sometimes|string|max:255',
            'NIK' => 'sometimes|string|size:16',
            'Norek' => 'sometimes|string|max:20',
            'Nama_Ibu' => 'sometimes|string|max:255',
            'Pekerjaan' => 'sometimes|string|max:255',
            'Gaji' => 'sometimes|string|max:16',
            'alamat' => 'sometimes|string',
            'kode_bank' => 'sometimes|string|exists:banks,kode_bank',
        ]);

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Prevent changing role to owner if current user is owner
        if (isset($validated['role']) && $validated['role'] === 'owner' && $request->user()->role === 'owner') {
            return response()->json(['message' => 'Cannot change role to owner'], 403);
        }

        $user->update($validated);

        // Log the update action
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user_updated',
            'entity_type' => 'App\Models\User',
            'entity_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $user->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'data' => $user->load('bank'),
            'message' => 'User updated successfully'
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Authorize using policy
        $this->authorize('delete', $user);

        // Prevent deleting self
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 403);
        }

        $userData = $user->toArray();
        $user->delete();

        // Log the delete action
        AuditLog::create([
            'user_id' => request()->user()->id,
            'action' => 'user_deleted',
            'entity_type' => 'App\Models\User',
            'entity_id' => $user->id,
            'old_values' => $userData,
            'new_values' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}