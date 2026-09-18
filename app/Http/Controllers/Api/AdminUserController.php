<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminUserController extends Controller
{
    protected WhatsAppService $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Tambah User Baru oleh Admin
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'role' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $division = match($validated['role']) {
            'Admin SCM' => 'Supply Chain Management',
            'Gudang', 'Staff Gudang' => 'Operasional Gudang & Logistik',
            'Finance', 'Tim Pajak', 'Staff Finance' => 'Tax & Finance Compliance',
            'SCM', 'Staf SCM', 'Staff SCM' => 'Supply Chain Management',
            default => 'SCM Operations'
        };

        $words = explode(' ', trim($validated['name']));
        $initials = count($words) >= 2
            ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
            : strtoupper(substr($validated['name'], 0, 2));

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'division' => $division,
            'initials' => $initials,
            'status' => 'approved',
            'password' => Hash::make($validated['password']),
            'approved_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} berhasil ditambahkan.",
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'division' => $user->division,
                'initials' => $user->initials,
                'registered_at' => $user->created_at ? $user->created_at->format('Y-m-d') : '-',
                'approved_at' => $user->approved_at ? $user->approved_at->format('Y-m-d H:i') : null,
            ]
        ]);
    }

    /**
     * Daftar Semua Pengguna
     */
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->get()->map(function ($u) {
            return [
                'id' => (string) $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role,
                'status' => $u->status,
                'division' => $u->division,
                'initials' => $u->initials,
                'registered_at' => $u->created_at ? $u->created_at->format('Y-m-d') : '-',
                'approved_at' => $u->approved_at ? $u->approved_at->format('Y-m-d H:i') : null,
            ];
        });

        $pendingCount = $users->where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'users' => $users,
            'pending_count' => $pendingCount
        ]);
    }

    /**
     * Persetujuan (ACC) Akun Baru oleh Admin
     */
    public function approve(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $adminId = Auth::id() ?: User::where('role', 'Admin SCM')->value('id');

        $user->update([
            'status' => 'approved',
            'approved_by' => $adminId,
            'approved_at' => Carbon::now(),
        ]);

        // Kirim notifikasi konfirmasi ke WhatsApp pemohon
        if (!empty($user->phone)) {
            $this->whatsapp->sendAccountApprovedNotice($user->phone, $user->name, $user->role);
        }

        return response()->json([
            'success' => true,
            'message' => "Akun {$user->name} berhasil disetujui (ACC). Notifikasi WhatsApp telah dikirimkan.",
            'user' => $user
        ]);
    }

    /**
     * Tolak Pengajuan Akun
     */
    public function reject(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pengajuan akun {$user->name} telah ditolak.",
            'user' => $user
        ]);
    }

    /**
     * Update Data User oleh Admin
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'role' => 'required|string',
            'status' => 'nullable|string|in:approved,pending,rejected',
            'password' => 'nullable|string|min:6',
        ]);

        $division = match($validated['role']) {
            'Admin SCM' => 'Supply Chain Management',
            'Gudang', 'Staff Gudang' => 'Operasional Gudang & Logistik',
            'Finance', 'Tim Pajak', 'Staff Finance' => 'Tax & Finance Compliance',
            'SCM', 'Staf SCM', 'Staff SCM' => 'Supply Chain Management',
            default => 'SCM Operations'
        };

        $words = explode(' ', trim($validated['name']));
        $initials = count($words) >= 2
            ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
            : strtoupper(substr($validated['name'], 0, 2));

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'division' => $division,
            'initials' => $initials,
        ];

        if (!empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
            if ($validated['status'] === 'approved' && !$user->approved_at) {
                $updateData['approved_at'] = Carbon::now();
            }
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "Data pengguna {$user->name} berhasil diperbarui.",
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'division' => $user->division,
                'initials' => $user->initials,
                'registered_at' => $user->created_at ? $user->created_at->format('Y-m-d') : '-',
                'approved_at' => $user->approved_at ? $user->approved_at->format('Y-m-d H:i') : null,
            ]
        ]);
    }

    /**
     * Hapus Akun
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->email === 'admin@scm.corp') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Super Administrator utama tidak dapat dihapus.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun pengguna berhasil dihapus.'
        ]);
    }
}
