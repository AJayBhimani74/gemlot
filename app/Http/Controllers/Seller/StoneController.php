<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoneRequest;
use App\Models\Stone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class StoneController extends Controller
{
    public function index(Request $request)
    {
        $stones = $request->user()
            ->stones()
            ->latest()
            ->paginate(15);

        return view('seller.stones.index', compact('stones'));
    }

    public function create()
    {
        return view('seller.stones.create', ['stone' => new Stone(['status' => Stone::STATUS_AVAILABLE])]);
    }

    public function store(StoneRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('stones', 'public');
        }

        $request->user()->stones()->create($data);

        return redirect()->route('seller.stones.index')->with('status', 'Stone added.');
    }

    public function edit(Stone $stone)
    {
        Gate::authorize('update', $stone);

        return view('seller.stones.edit', compact('stone'));
    }

    public function update(StoneRequest $request, Stone $stone): RedirectResponse
    {
        Gate::authorize('update', $stone);

        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            if ($stone->image_path) {
                Storage::disk('public')->delete($stone->image_path);
            }

            $data['image_path'] = $request->file('image')->store('stones', 'public');
        }

        $stone->update($data);

        return redirect()->route('seller.stones.index')->with('status', 'Stone updated.');
    }

    public function destroy(Stone $stone): RedirectResponse
    {
        Gate::authorize('delete', $stone);

        $stone->delete();

        return redirect()->route('seller.stones.index')->with('status', 'Stone removed.');
    }
}
