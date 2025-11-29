@props(['variant' => 'primary'])
<button {{ $attributes->class([
  'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition',
  'bg-indigo-600 text-white hover:bg-indigo-700' => $variant==='primary',
  'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50' => $variant==='secondary',
]) }}>
  {{ $slot }}
</button>
