{{--
    Generic input field. Error diambil langsung dari $errors bawaan Laravel,
    tidak perlu Alpine formErrors.

    USAGE:
    <x-ui.form-input name="nama_lengkap" label="Nama Lengkap" maxlength="30" required />
    <x-ui.form-input name="password" label="Password" type="password" />

    Untuk edit form dengan Alpine x-model, pass via $attributes:
    <x-ui.form-input name="username" label="Username" x-model="editData.username" required />

    Semua atribut HTML standar (placeholder, maxlength, x-model, dll)
    diteruskan otomatis via $attributes.
--}}

@props([
    'name',
    'label',
    'type'     => 'text',
    'required' => false,
])

<div class="form-control">
    <label class="label" for="{{ $name }}">
        <span class="label-text font-semibold text-sm">
            {{ $label }}
            @if ($required)<span class="text-red-500">*</span>@endif
        </span>
    </label>

    <input
        id="{{ $name }}"
        type="{{ $type }}"
        name="{{ $name }}"
        @if ($required) required @endif
        {{ $attributes->class([
            'input input-bordered input-sm',
            'input-error' => $errors->has($name),
        ]) }}
    />

    @error($name)
        <span class="mt-1 text-xs text-red-500">{{ $message }}</span>
    @enderror
</div>