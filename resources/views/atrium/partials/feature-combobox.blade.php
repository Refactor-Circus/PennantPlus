{{--
    A text field that filters the discovered features as the user types.
    Free text is still submitted, so features Pennant has not discovered
    remain usable.
--}}
<div class="relative flex w-full flex-col gap-1.5 text-on-surface dark:text-on-surface-dark"
     x-data="atriumPennantFeature(@js($features), @js($value))" x-on:click.outside="open = false">
    <label for="{{ $id }}" class="w-fit text-sm font-medium text-on-surface-strong dark:text-on-surface-dark-strong">
        {{ __('pennantplus::pennantplus.feature') }}
        @if ($required ?? false)
            <span class="text-danger" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="relative">
        <input id="{{ $id }}" name="feature" type="text" autocomplete="off" role="combobox"
               aria-autocomplete="list" aria-controls="{{ $id }}-options" x-bind:aria-expanded="open"
               @if ($required ?? false) required @endif
               @isset($placeholder) placeholder="{{ $placeholder }}" @endisset
               value="{{ $value }}"
               class="h-9 w-full rounded-radius border border-outline bg-surface pl-3 pr-9 text-sm text-on-surface-strong shadow-xs transition placeholder:text-on-surface/60 hover:border-on-surface/30 focus:border-primary focus:outline-none focus:ring-3 focus:ring-primary/15 dark:border-outline-dark dark:bg-white/5 dark:text-on-surface-dark-strong dark:placeholder:text-on-surface-dark/60 dark:hover:border-white/20 dark:focus:border-primary-dark dark:focus:ring-primary-dark/20"
               x-model="query" x-on:focus="show()" x-on:click="show()" x-on:input="show()"
               x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
               x-on:keydown.enter="choose($event)" x-on:keydown.escape="open = false" x-on:keydown.tab="open = false"
               data-testid="{{ $testid }}">

        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pointer-events-none absolute right-2.5 top-1/2 size-4 -translate-y-1/2 opacity-60" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </div>

    <ul id="{{ $id }}-options" role="listbox" x-show="open" x-cloak
        class="absolute top-full z-20 mt-1.5 max-h-60 w-full overflow-y-auto rounded-radius border border-outline bg-surface p-1 text-sm shadow-xl dark:border-outline-dark dark:bg-surface-dark">
        <template x-for="(feature, index) in matches" x-bind:key="feature">
            <li role="option" x-bind:aria-selected="index === active">
                <button type="button" tabindex="-1"
                        class="flex w-full cursor-pointer rounded-md px-2 py-1.5 text-left break-all text-on-surface-strong transition-colors hover:bg-on-surface-strong/5 dark:text-on-surface-dark-strong dark:hover:bg-white/5"
                        x-bind:class="index === active && 'bg-on-surface-strong/5 dark:bg-white/5'"
                        x-on:mouseenter="active = index" x-on:click="pick(feature)" x-text="feature"></button>
            </li>
        </template>
        <li x-show="matches.length === 0" class="px-3 py-1.5 opacity-75">{{ __('pennantplus::pennantplus.no_features') }}</li>
    </ul>

    @error('feature')
        <small class="text-xs text-danger">{{ $message }}</small>
    @enderror
</div>
