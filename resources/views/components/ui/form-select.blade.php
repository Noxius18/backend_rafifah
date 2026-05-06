{{--
    Generic select field. Error diambil langsung dari $errors bawaan Laravel.

    USAGE:
    <x-ui.form-select
        name="jabatan"
        label="Jabatan"
        :options="['Pengawas' => 'Pengawas', 'Panitia' => 'Panitia', 'Penguji' => 'Penguji']"
        required
    />

    Untuk edit form, gunakan :selected agar nilai terpilih sesuai data:
    <x-ui.form-select
        name="jabatan"
        label="Jabatan"
        :options="['Pengawas' => 'Pengawas', 'Panitia' => 'Panitia', 'Penguji' => 'Penguji']"
        :selected="$panitia->jabatan"
        required
    />

    Placeholder bisa dikosongkan dengan placeholder=""
    untuk select tanpa opsi kosong di bagian atas.
--}}

@props([
    'name',
    'label',
    'options'     => [],
    'selected'    => null,
    'placeholder' => '-- Pilih --',
    'required'    => false,
])

<div class="form-control">
    <label class="label" for="{{ $name }}">
        <span class="label-text font-semibold text-sm">
            {{ $label }}
            @if ($required)<span class="text-red-500">*</span>@endif
        </span>
    </label>

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($required) required @endif
        {{ $attributes->class([
            'select select-bordered select-sm',
            'select-error' => $errors->has($name),
        ]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected((old($name, $selected)) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>

    @error($name)
        <span class="mt-1 text-xs text-red-500">{{ $message }}</span>
    @enderror
</div>