<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Stone;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $inquiries = Inquiry::query()
            ->with(['stone', 'buyer:id,name,company_name'])
            ->when(
                $user->isSeller(),
                fn ($q) => $q->whereHas('stone', fn ($s) => $s->where('seller_id', $user->id)),
                fn ($q) => $q->where('buyer_id', $user->id),
            )
            ->latest()
            ->paginate(15);

        return view('inquiries.index', compact('inquiries'));
    }

    public function store(Request $request, Stone $stone): RedirectResponse
    {
        abort_unless($stone->status === Stone::STATUS_AVAILABLE, 404);

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
            'offered_price' => ['nullable', 'numeric', 'min:1', 'max:100000000'],
        ]);

        $alreadyOpen = Inquiry::where('stone_id', $stone->id)
            ->where('buyer_id', $request->user()->id)
            ->where('status', Inquiry::STATUS_OPEN)
            ->exists();

        if ($alreadyOpen) {
            return back()->withErrors(['message' => 'You already have an open inquiry on this stone.']);
        }

        $request->user()->inquiries()->create($data + ['stone_id' => $stone->id]);

        return redirect()->route('inquiries.index')->with('status', 'Inquiry sent to the seller.');
    }

    public function accept(Inquiry $inquiry, OrderService $orders): RedirectResponse
    {
        Gate::authorize('respond', $inquiry);

        try {
            $order = $orders->acceptInquiry($inquiry);
        } catch (DomainException $e) {
            return back()->withErrors(['inquiry' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', 'Inquiry accepted and order created.');
    }

    public function decline(Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('respond', $inquiry);

        $inquiry->update(['status' => Inquiry::STATUS_DECLINED, 'responded_at' => now()]);

        return back()->with('status', 'Inquiry declined.');
    }

    public function withdraw(Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('withdraw', $inquiry);

        $inquiry->update(['status' => Inquiry::STATUS_WITHDRAWN]);

        return back()->with('status', 'Inquiry withdrawn.');
    }
}
