@props(['href', 'preview' => false, 'label' => null])
<a href="{{ $href }}" @if ($preview) target="_blank" rel="noopener noreferrer" @endif
   @if ($label) aria-label="{{ $label }}{{ $preview ? ' (opens in a new tab)' : '' }}" @endif
   {{ $attributes->class(['inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-800']) }}>
    {{ $slot }}
    @if ($preview)
        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 14 15 5M6 5h9v9" stroke-linecap="round" stroke-linejoin="round" /></svg>
    @endif
</a>
