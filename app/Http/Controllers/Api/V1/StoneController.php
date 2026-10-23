<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoneResource;
use App\Models\Stone;
use Illuminate\Http\Request;

class StoneController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only([
            'shape', 'color', 'clarity', 'lab',
            'carat_min', 'carat_max', 'price_min', 'price_max',
        ]);

        $stones = Stone::query()
            ->catalogue()
            ->with('seller:id,company_name,country')
            ->filter($filters)
            ->sortBy($request->query('sort'))
            ->paginate(min((int) $request->query('per_page', 20), 50))
            ->withQueryString();

        return StoneResource::collection($stones);
    }

    public function show(Stone $stone): StoneResource
    {
        abort_unless(
            Stone::catalogue()->whereKey($stone->id)->exists(),
            404
        );

        return new StoneResource($stone->load('seller:id,company_name,country'));
    }
}
