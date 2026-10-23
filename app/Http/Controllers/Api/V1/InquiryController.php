<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InquiryResource;
use App\Models\Inquiry;
use App\Models\Stone;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $inquiries = Inquiry::query()
            ->with(['stone.seller:id,company_name,country', 'buyer:id,company_name', 'order:id,inquiry_id'])
            ->visibleTo($request->user())
            ->latest()
            ->paginate(20);

        return InquiryResource::collection($inquiries);
    }

    public function store(Request $request, Stone $stone): JsonResponse
    {
        abort_unless(Stone::catalogue()->whereKey($stone->id)->exists(), 404);

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
            'offered_price' => ['nullable', 'numeric', 'min:1', 'max:100000000'],
        ]);

        $alreadyOpen = Inquiry::where('stone_id', $stone->id)
            ->where('buyer_id', $request->user()->id)
            ->where('status', Inquiry::STATUS_OPEN)
            ->exists();

        if ($alreadyOpen) {
            return response()->json([
                'message' => 'You already have an open inquiry on this stone.',
            ], 422);
        }

        $inquiry = $request->user()->inquiries()->create($data + [
            'stone_id' => $stone->id,
            'status' => Inquiry::STATUS_OPEN,
        ]);

        return (new InquiryResource($inquiry->load('stone')))
            ->response()
            ->setStatusCode(201);
    }

    public function accept(Inquiry $inquiry, OrderService $orders): JsonResponse
    {
        Gate::authorize('respond', $inquiry);

        try {
            $order = $orders->acceptInquiry($inquiry);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Inquiry accepted.', 'order_id' => $order->id], 201);
    }

    public function decline(Inquiry $inquiry): InquiryResource
    {
        Gate::authorize('respond', $inquiry);

        $inquiry->update(['status' => Inquiry::STATUS_DECLINED, 'responded_at' => now()]);

        return new InquiryResource($inquiry);
    }

    public function withdraw(Inquiry $inquiry): InquiryResource
    {
        Gate::authorize('withdraw', $inquiry);

        $inquiry->update(['status' => Inquiry::STATUS_WITHDRAWN]);

        return new InquiryResource($inquiry);
    }
}
