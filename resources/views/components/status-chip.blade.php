@props(['status' => null])
@php
    $map = [
        'created' => ['Created', 'bg-green-100 text-green-800'],
        'draft' => ['Draft', 'bg-yellow-100 text-yellow-800'],
    ];
    [$label, $classes] = $map[$status] ?? ['Empty', 'bg-gray-100 text-gray-500'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium $classes"]) }}>{{ $label }}</span>