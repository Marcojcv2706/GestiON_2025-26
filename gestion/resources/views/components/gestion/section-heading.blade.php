@props(['eyebrow', 'title', 'description' => null])

<div class="section-heading">
    <p class="eyebrow"><span class="eyebrow-line"></span>{{ $eyebrow }}</p>
    <h2>{{ $title }}</h2>
    @if($description)
        <p class="section-description">{{ $description }}</p>
    @endif
</div>
