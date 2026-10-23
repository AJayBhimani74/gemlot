@extends('layouts.app')

@section('title', 'Catalogue')

@section('content')
    <div class="catalogue">
        <aside class="card filters">
            <form method="GET" action="{{ route('catalogue.index') }}">
                @foreach (['shape' => \App\Models\Stone::SHAPES, 'color' => \App\Models\Stone::COLORS, 'clarity' => \App\Models\Stone::CLARITIES, 'lab' => \App\Models\Stone::LABS] as $key => $options)
                    <fieldset>
                        <legend>{{ ucfirst($key) }}</legend>
                        @foreach ($options as $option)
                            <label class="check">
                                <input type="checkbox" name="{{ $key }}[]" value="{{ $option }}" @checked(in_array($option, (array) ($filters[$key] ?? [])))>
                                {{ $option }}
                            </label>
                        @endforeach
                    </fieldset>
                @endforeach

                <fieldset>
                    <legend>Carat</legend>
                    <div class="row">
                        <input type="number" step="0.01" name="carat_min" placeholder="Min" value="{{ $filters['carat_min'] ?? '' }}">
                        <input type="number" step="0.01" name="carat_max" placeholder="Max" value="{{ $filters['carat_max'] ?? '' }}">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Total price (USD)</legend>
                    <div class="row">
                        <input type="number" name="price_min" placeholder="Min" value="{{ $filters['price_min'] ?? '' }}">
                        <input type="number" name="price_max" placeholder="Max" value="{{ $filters['price_max'] ?? '' }}">
                    </div>
                </fieldset>

                <input type="hidden" name="sort" value="{{ $sort }}">
                <button class="btn" type="submit">Apply</button>
                <a href="{{ route('catalogue.index') }}" class="muted">Reset</a>
            </form>
        </aside>

        <section>
            <div class="head">
                <h1>{{ $stones->total() }} {{ Str::plural('stone', $stones->total()) }}</h1>
                <form method="GET" action="{{ route('catalogue.index') }}">
                    @foreach (request()->except('sort', 'page') as $name => $value)
                        @foreach ((array) $value as $v)
                            <input type="hidden" name="{{ $name }}{{ is_array($value) ? '[]' : '' }}" value="{{ $v }}">
                        @endforeach
                    @endforeach
                    <select name="sort" onchange="this.form.submit()">
                        @foreach (['newest' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'carat_asc' => 'Carat: low to high', 'carat_desc' => 'Carat: high to low'] as $key => $label)
                            <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if ($stones->isEmpty())
                <div class="card"><p class="muted">No stones match these filters.</p></div>
            @else
                <div class="grid">
                    @foreach ($stones as $stone)
                        <a class="stone" href="{{ route('catalogue.show', $stone) }}">
                            <div class="thumb">
                                @if ($stone->image_url)
                                    <img src="{{ $stone->image_url }}" alt="{{ $stone->shape }} {{ $stone->carat }}ct">
                                @endif
                            </div>
                            <div class="info">
                                <strong>{{ $stone->carat }} ct {{ $stone->shape }}</strong>
                                <span class="muted">{{ $stone->color }} / {{ $stone->clarity }}@if ($stone->lab) &middot; {{ $stone->lab }}@endif</span>
                                <span class="price">${{ number_format($stone->total_price) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{ $stones->links() }}
            @endif
        </section>
    </div>
@endsection
