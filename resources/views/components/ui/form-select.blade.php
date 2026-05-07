{{--
    Professional select field with daisyUI styling.
    Supports icon, helper text, and inline error messages.

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
    'icon'        => null,
    'helper'      => null,
])

@php
    $hasError = $errors->has($name);
    $iconPaths = [
        'user'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />',
        'briefcase' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />',
        'tag'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />',
        'globe'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />',
    ];
@endphp

<div class="form-control w-full">
    <label class="label pb-1.5" for="{{ $name }}">
        <span class="label-text font-medium text-slate-700">
            {{ $label }}
            @if ($required)<span class="text-red-500 ml-0.5">*</span>@endif
        </span>
    </label>

    <div class="relative">
        @if ($icon && isset($iconPaths[$icon]))
            <span class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3 text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    {!! $iconPaths[$icon] !!}
                </svg>
            </span>
        @endif

        <select
            id="{{ $name }}"
            name="{{ $name }}"
            @if ($required) required @endif
            {{ $attributes->class([
                'select select-bordered w-full text-sm transition duration-150',
                'select-sm',
                'pl-9' => $icon && isset($iconPaths[$icon]),
                'border-red-400 focus:border-red-500 focus:ring-red-200' => $hasError,
                'focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100' => !$hasError,
            ]) }}
        >
            @if ($placeholder)
                <option value="" disabled>{{ $placeholder }}</option>
            @endif

            @foreach ($options as $value => $label)
                <option value="{{ $value }}" @selected((old($name, $selected)) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        @if ($hasError)
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </span>
        @endif
    </div>

    @if ($helper && !$hasError)
        <label class="label pt-1 pb-0">
            <span class="label-text-alt text-slate-400">{{ $helper }}</span>
        </label>
    @endif

    @error($name)
        <label class="label pt-1 pb-0">
            <span class="label-text-alt text-red-500 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L4.082 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                {{ $message }}
            </span>
        </label>
    @enderror
</div>