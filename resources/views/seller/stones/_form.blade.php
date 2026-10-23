
<div class="row">
    <div>
        <label for="shape">Shape</label>
        <select id="shape" name="shape" required>
            @foreach (\App\Models\Stone::SHAPES as $shape)
                <option value="{{ $shape }}" @selected(old('shape', $stone->shape) === $shape)>{{ $shape }}</option>
            @endforeach
        </select>
        @error('shape') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div>
        <label for="carat">Carat</label>
        <input id="carat" name="carat" type="number" step="0.01" value="{{ old('carat', $stone->carat) }}" required>
        @error('carat') <div class="error">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div>
        <label for="color">Colour</label>
        <select id="color" name="color" required>
            @foreach (\App\Models\Stone::COLORS as $color)
                <option value="{{ $color }}" @selected(old('color', $stone->color) === $color)>{{ $color }}</option>
            @endforeach
        </select>
        @error('color') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div>
        <label for="clarity">Clarity</label>
        <select id="clarity" name="clarity" required>
            @foreach (\App\Models\Stone::CLARITIES as $clarity)
                <option value="{{ $clarity }}" @selected(old('clarity', $stone->clarity) === $clarity)>{{ $clarity }}</option>
            @endforeach
        </select>
        @error('clarity') <div class="error">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div>
        <label for="cut">Cut</label>
        <select id="cut" name="cut">
            <option value="">-</option>
            @foreach (\App\Models\Stone::CUTS as $cut)
                <option value="{{ $cut }}" @selected(old('cut', $stone->cut) === $cut)>{{ $cut }}</option>
            @endforeach
        </select>
        @error('cut') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div>
        <label for="lab">Lab</label>
        <select id="lab" name="lab">
            <option value="">-</option>
            @foreach (\App\Models\Stone::LABS as $lab)
                <option value="{{ $lab }}" @selected(old('lab', $stone->lab) === $lab)>{{ $lab }}</option>
            @endforeach
        </select>
        @error('lab') <div class="error">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div>
        <label for="certificate_no">Certificate no.</label>
        <input id="certificate_no" name="certificate_no" value="{{ old('certificate_no', $stone->certificate_no) }}">
        @error('certificate_no') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div>
        <label for="price_per_carat">Price per carat (USD)</label>
        <input id="price_per_carat" name="price_per_carat" type="number" step="0.01" value="{{ old('price_per_carat', $stone->price_per_carat) }}" required>
        @error('price_per_carat') <div class="error">{{ $message }}</div> @enderror
    </div>
</div>

@if ($stone->exists)
    <label for="status">Status</label>
    <select id="status" name="status">
        <option value="available" @selected(old('status', $stone->status) === 'available')>Available</option>
        <option value="on_hold" @selected(old('status', $stone->status) === 'on_hold')>On hold</option>
    </select>
@endif

<label for="image">Photo</label>
<input id="image" name="image" type="file" accept="image/*">
@error('image') <div class="error">{{ $message }}</div> @enderror

<label for="notes">Notes</label>
<textarea id="notes" name="notes" rows="3">{{ old('notes', $stone->notes) }}</textarea>
@error('notes') <div class="error">{{ $message }}</div> @enderror
