@php $editing = isset($product); @endphp

<form method="POST" enctype="multipart/form-data"
      action="{{ $editing ? route('products.update', $product) : route('products.store') }}"
      class="card space-y-6 p-8 shadow-[0_4px_16px_rgba(0,106,65,.06)]"
      x-data="{ preview: @js($editing ? $product->photo_url : null), hasPhoto: {{ $editing && $product->photo_path ? 'true' : 'false' }},
                pick(e) { const f = e.target.files[0]; if (!f) return; this.preview = URL.createObjectURL(f); this.hasPhoto = true; } }">
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

    {{-- Expiration / Category --}}
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="expiration_date" class="label">Expiration Date <span class="text-danger">*</span></label>
            <div class="relative">
                <x-icon name="calendar" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                <input id="expiration_date" name="expiration_date" type="date" value="{{ old('expiration_date', isset($product) ? $product->expiration_date->format('Y-m-d') : '') }}" required class="input input-icon">
            </div>
            @error('expiration_date') <p class="field-error">{{ $message }}</p> @enderror
            <p class="hint">Required for FDA shelf-life compliance tracking</p>
        </div>
        <div>
            <label for="category" class="label">Classification <span class="text-danger">*</span></label>
            <select id="category" name="category" class="input">
                @foreach (['otc' => 'OTC Medicine', 'rx' => 'Rx Prescription', 'both' => 'Prescription & OTC'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('category', $product->category ?? 'otc') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            @error('category') <p class="field-error">{{ $message }}</p> @enderror
            <p class="hint">Used for the Rx / OTC filters</p>
        </div>
    </div>

    {{-- Photo --}}
    <div>
        <span class="label">Product Photo</span>
        <div class="flex flex-wrap items-center gap-5 rounded-xl border border-mint-2 bg-mint-3 p-4">
            <div class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-lg border-2 border-dashed border-brand/30 bg-white text-center text-[11px] font-semibold text-ink" :class="hasPhoto && 'border-solid border-mint-2'">
                <template x-if="hasPhoto"><img :src="preview" alt="Product preview" class="h-full w-full object-cover"></template>
                <template x-if="!hasPhoto"><span><x-icon name="camera" class="mx-auto mb-1 h-6 w-6 text-brand-dark" />No Image</span></template>
            </div>
            <div>
                <input x-ref="file" id="photo" name="photo" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick($event)">
                <button type="button" @click="$refs.file.click()" class="btn-outline btn-sm px-4! py-2! text-brand-dark" style="border-color: var(--color-brand)">
                    <x-icon :name="$editing ? 'camera' : 'upload'" class="h-4 w-4" /> {{ $editing ? 'Change Photo' : 'Upload Photo' }}
                </button>
                <p class="mt-2 text-xs text-muted">PNG, JPG or WEBP up to 5MB</p>
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
