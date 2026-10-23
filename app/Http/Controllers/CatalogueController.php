<?php

namespace App\Http\Controllers;

use App\Models\Stone;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only([
            'shape', 'color', 'clarity', 'lab',
            'carat_min', 'carat_max', 'price_min', 'price_max',
        ]);

        $stones = Stone::query()
            ->available()
            ->whereHas('seller', fn ($q) => $q->where('is_approved', true))
            ->filter($filters)
            ->sortBy($request->query('sort'))
            ->paginate(12)
            ->withQueryString();

        return view('catalogue.index', [
            'stones' => $stones,
            'filters' => $filters,
            'sort' => $request->query('sort', 'newest'),
        ]);
    }

    public function show(Stone $stone)
    {
        abort_unless($stone->status === Stone::STATUS_AVAILABLE, 404);

        return view('catalogue.show', ['stone' => $stone->load('seller:id,company_name,country')]);
    }
}
