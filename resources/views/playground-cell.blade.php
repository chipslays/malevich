<article class="mlv-card" data-mlv-theme-target data-mlv-label="{{ $cell->label() }}">
    <div class="mlv-stage" data-mlv-canvas>
        @if ($cell->error !== null)
            <span class="mlv-error">{!! $icon('alert') !!}<span>{{ $cell->error }}</span></span>
        @else
            {!! $cell->html !!}
        @endif
    </div>
    <footer class="mlv-foot">
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
            <button class="mlv-icon" type="button" data-mlv-card-theme title="Dark / light for this preview">{!! $icon('moon', 'i-moon') !!}{!! $icon('sun', 'i-sun') !!}</button>
            @if ($cell->html !== null)
                <button class="mlv-icon" type="button" data-mlv-code title="View the code">{!! $icon('code') !!}</button>
            @endif
            <button class="mlv-icon" type="button" data-mlv-copy="{{ $cell->tag }}" title="Copy the tag">{!! $icon('copy', 'i-copy') !!}{!! $icon('check', 'i-done') !!}</button>
        </span>
    </footer>
    <textarea hidden data-mlv-source>{{ $cell->html }}</textarea>
</article>
