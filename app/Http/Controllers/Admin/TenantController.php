<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()
            ->whereIn('role', ['penyewa', 'calon_penyewa'])
            ->with(['tenantProfile', 'leases.room.roomType']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->whereHas('leases', fn ($l) => $l->where('status', 'active'));
            } elseif ($status === 'alumni') {
                $query->whereDoesntHave('leases', fn ($l) => $l->where('status', 'active'))
                    ->whereHas('leases');
            }
        }

        $tenants = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(User $tenant): Response
    {
        $tenant->load([
            'tenantProfile',
            'leases.room.roomType',
            'invoices.items',
            'invoices.payments',
            'reservations.room.roomType',
        ]);

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant,
        ]);
    }
}
