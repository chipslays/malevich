<div class="mlv-card">
    <div class="mlv-stage">
        @if ($cell->error !== null)
            <span class="mlv-error">{!! $icon('alert') !!}<span>{{ $cell->error }}</span></span>
        @else
            {!! $cell->html !!}
        @endif
    </div>
    <div class="mlv-foot">
        <span class="mlv-value mono" title="{{ $cell->tag }}">
            @forelse ($cell->values as $option => $value)
                @if ($value === 'true')
                    <b>{{ $option }}</b>
                @elseif ($value === 'false')
                    :{{ $option }}=<b>false</b>
                @else
                    {{ $option }}=<b>"{{ $value }}"</b>
                @endif
            @empty
                <b>default</b>
            @endforelse
        </span>
        <span class="mlv-actions">
            @if ($cell->html !== null)
                <button class="mlv-icon" type="button" data-mlv-html aria-expanded="false" title="Show the HTML">{!! $icon('code') !!}</button>
            @endif
            <button class="mlv-icon" type="button" data-mlv-copy="{{ $cell->tag }}" title="Copy the tag">{!! $icon('copy') !!}</button>
        </span>
    </div>
    @if ($cell->html !== null)
        <div class="mlv-code"><pre class="mono">{{ $cell->html }}</pre></div>
    @endif
</div>
