@props(['size' => 'sm', 'variant' => 'solid', 'inset' => false, 'iconOnly' => false])

@size(['sm' => 'h-8', 'lg' => 'h-12'])
@cases('size', [
    'sm' => ['-mx-2' => ! $iconOnly],
    'lg' => ['-mx-5' => ! $iconOnly],
], when: ['inset' => true, 'variant' => ['ghost', 'link']])

@cases('size', [
    'sm' => ['icon' => ['size-3', 'opacity-50' => $iconOnly]],
    'lg' => ['icon' => 'size-5'],
], when: ['inset' => true])

<button @attributes>
    @styled('icon')<i @attributes('icon')></i>@endstyled
    {{ $slot }}
</button>
