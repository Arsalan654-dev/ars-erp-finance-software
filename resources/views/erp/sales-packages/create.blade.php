@extends('erp.layouts.app')
@section('title', 'New Sales Package')
@section('content')
<div class="reveal max-w-2xl mx-auto">
    <a href="{{ route('erp.sales-packages.index') }}" style="font-size:12px;color:var(--gray-400);text-decoration:none;">← Back to Sales Packages</a>
    <h1 class="ks-page-title" style="margin-top:6px;">New Sales Package</h1>

    <div class="ks-card"><div class="ks-card-body">
        <form method="POST" action="{{ route('erp.sales-packages.store') }}" class="space-y-4">
            @csrf
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Package Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Basic SEO Package" class="ks-form-input">
                @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="ks-form-input" placeholder="What's included in this package">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Unit Price *</label>
                    <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price') }}" required class="ks-form-input">
                    <p class="text-xs text-gray-400 mt-1">Auto-calculated from bundle lines when lines are added.</p>
                    @error('unit_price')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Currency *</label>
                    <select name="currency" class="ks-form-input">
                        @foreach(['USD','GBP','EUR'] as $cur)
                            <option value="{{ $cur }}" @selected(old('currency', 'USD') === $cur)>{{ $cur }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bundle Lines <span class="text-gray-400 font-normal">(optional — a whole package dropped into quotations in one click)</span></label>
                <div id="pkg-items" class="space-y-2">
                    <div class="grid grid-cols-12 gap-2 items-center pkg-row">
                        <input name="items[0][description]" placeholder="Line description" class="col-span-6 ks-form-input">
                        <input name="items[0][quantity]" type="number" value="1" min="1" class="col-span-2 ks-form-input">
                        <input name="items[0][unit_price]" type="number" step="0.01" value="0" min="0" class="col-span-3 ks-form-input">
                        <button type="button" onclick="this.closest('.pkg-row').remove()" class="col-span-1 text-red-500 text-sm">✕</button>
                    </div>
                </div>
                <button type="button" onclick="addPkgRow()" class="mt-2 text-sm text-indigo-600 font-medium">+ Add Line</button>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active (available in quotation/invoice forms)
            </label>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="ks-btn ks-btn-primary">Create Package</button>
                <a href="{{ route('erp.sales-packages.index') }}" class="ks-btn ks-btn-light">Cancel</a>
            </div>
        </form>
    </div></div>
</div>
@push('scripts')
<script>
let pkgIndex = 1;
function addPkgRow() {
    const html = `<div class="grid grid-cols-12 gap-2 items-center pkg-row">
        <input name="items[${pkgIndex}][description]" placeholder="Line description" class="col-span-6 ks-form-input">
        <input name="items[${pkgIndex}][quantity]" type="number" value="1" min="1" class="col-span-2 ks-form-input">
        <input name="items[${pkgIndex}][unit_price]" type="number" step="0.01" value="0" min="0" class="col-span-3 ks-form-input">
        <button type="button" onclick="this.closest('.pkg-row').remove()" class="col-span-1 text-red-500 text-sm">✕</button>
    </div>`;
    document.getElementById('pkg-items').insertAdjacentHTML('beforeend', html);
    pkgIndex++;
}
</script>
@endpush
@endsection
