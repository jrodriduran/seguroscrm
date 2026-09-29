{{-- Name, tone and consent effect of a call outcome (add and edit forms). --}}
<div>
    <label class="tw-label">@lang('communications::app.outcomes.name')</label>
    <input type="text" name="name" maxlength="80" required class="tw-input" value="{{ $outcome?->label }}">
</div>

<div class="grid grid-cols-2 gap-2">
    <div>
        <label class="tw-label">@lang('communications::app.outcomes.tone')</label>
        <select name="tone" class="tw-input">
            @foreach (\Webkul\Communications\Models\CallOutcome::TONES as $tone)
                <option value="{{ $tone }}" @selected(($outcome?->tone ?? 'neutral') === $tone)>@lang('communications::app.outcomes.tones.'.$tone)</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="tw-label">@lang('communications::app.outcomes.revokes')</label>
        <select name="revokes" class="tw-input">
            <option value="">—</option>

            @foreach ([...\Webkul\Communications\Services\ConsentRegistry::CHANNELS, 'all'] as $channel)
                <option value="{{ $channel }}" @selected($outcome?->revokes === $channel)>@lang('communications::app.contact.channels.'.$channel)</option>
            @endforeach
        </select>
    </div>
</div>
