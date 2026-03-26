@if ($messages && count($messages))
    <p {{ $attributes->merge(['class' => 'text-sm text-red-600']) }}>
        {{ implode(' ', (array) $messages) }}
    </p>
@endif
