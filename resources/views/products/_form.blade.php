@php $editing = isset($product); @endphp

<form method="POST" enctype="multipart/form-data"
      action="{{ $editing ? route('products.update', $product) : route('products.store') }}"
      class="card space-y-6 p-8 shadow-[0_4px_16px_rgba(0,106,65,.06)]"
      x-data="{ preview: @js($editing ? $product->photo_url : null), hasPhoto: {{ $editing && $product->photo_path ? 'true' : 'false' }}, photoError: '', busy: false,
                type: @js(old('type', $product->type ?? 'generic')), noExpiry: @js((bool) old('no_expiry', $editing && $product->expiration_date === null)),
                get grocery() { return this.type === 'grocery'; },
                get dateOff() { return this.grocery && this.noExpiry; },
                init() { this.$watch('type', (v) => { if (v !== 'grocery') this.noExpiry = false; }); },
                async pick(e) {
                    this.photoError = ''; this.busy = true;
                    try { const url = await window.prepareImage(e.target); if (url) { this.preview = url; this.hasPhoto = true; } }
                    catch (err) { e.target.value = ''; this.photoError = err.message; }
                    this.busy = false;
                } }">
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- Name --}}
    <div>
        <div class="mb-1.5 flex items-center justify-between">
            <label for="name" class="text-sm font-bold text-ink">Product Name <span class="text-danger">*</span></label>
            <span class="text-xs text-muted">Generic and brand name with dosage</span>
        </div>
        <div class="relative">
            <x-icon name="pharmacy" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
            <input id="name" name="name" value="{{ old('name', $product->name ?? '') }}" required placeholder="e.g. Paracetamol Tablets 500mg (Biogesic)" class="input input-icon">
        </div>
        @error('name') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- Price / Stock --}}
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="price" class="label">Price (₱) <span class="text-danger">*</span></label>
            <div class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 font-semibold text-body">₱</span>
                <input id="price" name="price" type="number" step="0.01" min="0" inputmode="decimal" value="{{ old('price', isset($product) ? number_format($product->price, 2, '.', '') : '') }}" required placeholder="10.00" class="input pl-9">
            </div>
            @error('price') <p class="field-error">{{ $message }}</p> @enderror
            <p class="hint">Retail price per piece or blister pack</p>
        </div>
        <div>
            <label for="stock" class="label">How Many in Stock <span class="text-danger">*</span></label>
            <div class="relative">
                <input id="stock" name="stock" type="number" min="0" step="1" inputmode="numeric" value="{{ old('stock', $product->stock ?? '') }}" required placeholder="100" class="input pr-16">
                <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs font-medium text-body">units</span>
            </div>
            @error('stock') <p class="field-error">{{ $message }}</p> @enderror
            <p class="hint">{{ $editing ? 'Current quantity on dispensary shelves' : 'Initial stock quantity on dispensary shelves' }}</p>
        </div>
    </div>

    {{-- Expiration / Product Type --}}
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <div class="mb-1.5 flex items-center justify-between gap-2">
                <label for="expiration_date" class="text-sm font-semibold text-ink">Expiration Date <span class="text-danger" x-show="!dateOff">*</span></label>
                <label x-show="grocery" x-cloak class="flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-ink">
                    <input type="checkbox" name="no_expiry" value="1" x-model="noExpiry" :disabled="!grocery" @checked(old('no_expiry', $editing && $product->expiration_date === null)) class="h-4 w-4 rounded border-hint text-brand focus:ring-brand/30"> No expiry
                </label>
            </div>
            <div class="relative">
                <x-icon name="calendar" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                <input id="expiration_date" name="expiration_date" type="date" value="{{ old('expiration_date', $editing && $product->expiration_date ? $product->expiration_date->format('Y-m-d') : '') }}"
                       :required="!dateOff" :disabled="dateOff" class="input input-icon disabled:bg-mint-3 disabled:text-muted">
            </div>
            @error('expiration_date') <p class="field-error">{{ $message }}</p> @enderror
            <p class="hint" x-show="!grocery">Required for medicines (FDA shelf-life tracking)</p>
            <p class="hint" x-show="grocery" x-cloak>Tick <b>No expiry</b> for items that don't expire (napkin, soap, tissue).</p>
        </div>

        <div>
            <label for="type" class="label">Product Type <span class="text-danger">*</span></label>
            <select id="type" name="type" x-model="type" class="input">
                @foreach (\App\Models\Product::TYPES as $v => $l)
                    <option value="{{ $v }}" @selected(old('type', $product->type ?? 'generic') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            @error('type') <p class="field-error">{{ $message }}</p> @enderror
            <p class="hint">Branded, Generic or Grocery (same as the daily report)</p>

            {{-- Rx: para sa gamot lang, wala sa Grocery --}}
            <label x-show="!grocery" class="mt-3 flex cursor-pointer items-start gap-3 rounded-lg border border-warn-line bg-warn-bg px-3.5 py-3">
                <input type="checkbox" name="requires_rx" value="1" :disabled="grocery" @checked(old('requires_rx', $editing ? $product->isRx() : false)) class="mt-0.5 h-4 w-4 rounded border-warn text-brand focus:ring-brand/30">
                <span class="text-xs leading-snug text-body"><b class="text-ink">Needs a prescription (Rx)</b> — POS will ask to verify the doctor's prescription</span>
            </label>
        </div>
    </div>

    {{-- Unit / Subtitle --}}
    @php
        $units = ['piece', 'tablet', 'capsule', 'bottle', 'box', 'pack', 'sachet', 'strip', 'tube', 'vial', 'ampule', 'bar', 'can', 'bag', 'roll', 'set'];
        $currentUnit = old('unit', $product->unit ?? 'piece');
        if (! in_array($currentUnit, $units, true)) array_unshift($units, $currentUnit); // mga lumang unit na wala sa listahan
    @endphp
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="unit" class="label">Unit <span class="text-danger">*</span></label>
            <select id="unit" name="unit" required class="input">
                @foreach ($units as $u)
                    <option value="{{ $u }}" @selected($currentUnit === $u)>Per {{ $u }}</option>
                @endforeach
            </select>
            @error('unit') <p class="field-error">{{ $message }}</p> @enderror
            <p class="hint">How it is sold and priced (e.g. per piece, per bottle, per box)</p>
        </div>
        <div>
            <label for="subtitle" class="label">Subtitle <span class="font-normal text-muted">(Optional)</span></label>
            <input id="subtitle" name="subtitle" value="{{ old('subtitle', $product->subtitle ?? '') }}" maxlength="120" placeholder="e.g. Analgesic, Antibiotic, Vitamin" class="input">
            @error('subtitle') <p class="field-error">{{ $message }}</p> @enderror
            <p class="hint">Short type or use of the product</p>
        </div>
    </div>

    {{-- Photo --}}
    <div>
        <span class="label">Product Photo</span>
        <div class="flex flex-wrap items-center gap-5 rounded-xl border border-mint-2 bg-mint-3 p-4">
            <div class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-lg border-2 border-dashed border-brand/30 bg-white text-center text-[11px] font-semibold text-ink" :class="hasPhoto && 'border-solid border-mint-2'">
                <template x-if="hasPhoto"><img :src="preview" alt="Product preview" class="h-full w-full object-cover" onerror="this.onerror=null;this.src='{{ asset('images/no-image.svg') }}'"></template>
                <template x-if="!hasPhoto"><span><x-icon name="camera" class="mx-auto mb-1 h-6 w-6 text-brand-dark" />No Image</span></template>
            </div>
            <div>
                <input x-ref="file" id="photo" name="photo" type="file" accept="image/png,image/jpeg,image/webp,image/gif,image/bmp,image/avif" class="sr-only" @change="pick($event)">
                <button type="button" @click="$refs.file.click()" class="btn-outline btn-sm px-4! py-2! text-brand-dark" style="border-color: var(--color-brand)">
                    <x-icon :name="$editing ? 'camera' : 'upload'" class="h-4 w-4" /> {{ $editing ? 'Change Photo' : 'Upload Photo' }}
                </button>
                <p class="mt-2 text-xs text-muted">PNG, JPG, JPEG, WEBP, GIF or BMP. Large photos are resized automatically.</p>
                <p x-show="busy" x-cloak class="mt-1 text-xs font-medium text-brand-dark">Preparing photo…</p>
                <p x-show="photoError" x-text="photoError" x-cloak class="field-error" role="alert"></p>
            </div>
        </div>
        @error('photo') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- Description --}}
    <div>
        <div class="mb-1.5 flex items-center justify-between">
            <label for="description" class="text-sm font-bold text-ink">Short Description <span class="font-normal text-muted">(Optional)</span></label>
            <span class="text-xs text-muted">Storage instructions or pharmacist notes</span>
        </div>
        <textarea id="description" name="description" rows="4" maxlength="1000" placeholder="e.g. Standard 500mg pain relief tablets. Keep in a dry, cool cabinet below 30°C away from direct sunlight." class="input">{{ old('description', $product->description ?? '') }}</textarea>
        @error('description') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- Footer --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-mint-2 pt-6">
        @if ($editing)
            <button type="button" @click="$dispatch('open-delete')" class="btn-danger btn-sm px-4! py-2!"><x-icon name="trash" class="h-4 w-4" /> Delete This Product</button>
        @else
            <span class="pill-green px-3 py-1.5 text-xs"><x-icon name="circle-check" class="h-3.5 w-3.5" /> Item will instantly sync with dispensary inventory</span>
        @endif

        <div class="flex items-center gap-3">
            <a href="{{ $editing ? route('products.show', $product) : route('products.index') }}" class="btn bg-mint-2 text-body hover:bg-mint-3">Cancel</a>
            <button type="submit" class="btn-primary px-7"><x-icon name="check" class="h-4 w-4" /> {{ $editing ? 'Save Changes' : 'Save Product' }}</button>
        </div>
    </div>
</form>
