<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'pending');

        $sellers = User::query()
            ->where('role', User::ROLE_SELLER)
            ->withCount('stones')
            ->when($filter === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($filter === 'approved', fn ($q) => $q->where('is_approved', true))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.sellers.index', compact('sellers', 'filter'));
    }

    public function approve(User $seller): RedirectResponse
    {
        abort_unless($seller->isSeller(), 404);

        $seller->update(['is_approved' => true]);

        return back()->with('status', "{$seller->company_name} approved.");
    }

    public function revoke(User $seller): RedirectResponse
    {
        abort_unless($seller->isSeller(), 404);

        $seller->update(['is_approved' => false]);

        return back()->with('status', "{$seller->company_name} access revoked.");
    }
}
