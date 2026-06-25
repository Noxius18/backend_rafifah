@props([
    'alert'
])

<x-ui.alert type="error" :alert="$alert" {{ $attributes }} />
