{{-- Communications styles and helpers, loaded in <head> by the admin theme. --}}
<style>
    .cm-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .cm-chip {
        height: 28px; padding: 0 11px; border-radius: 99px;
        font-size: 12px; font-weight: 600; color: #475569;
        background: #fff; border: 1px solid rgba(15, 23, 42, 0.14);
        transition: border-color .15s, background .15s, color .15s;
    }
    .cm-chip:hover:not(:disabled) { border-color: rgba(79, 124, 255, 0.55); color: var(--nx-accent); }
    .cm-chip.is-on { color: #fff; border-color: transparent; background: linear-gradient(135deg, #4f6bff, #6d5dfc); box-shadow: 0 4px 12px -4px rgba(79, 107, 255, 0.55); }
    .cm-chip:disabled { cursor: default; opacity: .75; }
    .dark .cm-chip { background: #0b1220; color: #cbd5e1; border-color: rgba(148, 163, 184, 0.2); }
    .dark .cm-chip.is-on { color: #fff; background: linear-gradient(135deg, #4f6bff, #6d5dfc); }

    .cm-consents { display: flex; flex-direction: column; gap: 6px; }
    .cm-consent { display: grid; grid-template-columns: 78px auto minmax(0, 1fr); align-items: center; gap: 8px; font-size: 12.5px; }
    .cm-consent-channel { font-weight: 600; color: #334155; }
    .dark .cm-consent-channel { color: #e2e8f0; }
    .cm-consent-meta { text-align: right; }

    .cm-consent-form summary { list-style: none; cursor: pointer; width: 100%; justify-content: center; }
    .cm-consent-form summary::-webkit-details-marker { display: none; }
    .cm-consent-form[open] summary { opacity: .7; }

    .cm-outcome { display: inline-flex; align-items: center; gap: 5px; margin-left: 6px; padding: 1px 8px; border-radius: 99px; font-size: 11px; font-weight: 650; vertical-align: middle;
        color: var(--cm-c, #64748b); background: color-mix(in srgb, var(--cm-c, #64748b) 11%, transparent); border: 1px solid color-mix(in srgb, var(--cm-c, #64748b) 24%, transparent); }
    .cm-outcome.is-positive { --cm-c: #059669; }
    .cm-outcome.is-negative { --cm-c: #e11d48; }
    .cm-outcome.is-neutral { --cm-c: #64748b; }
</style>

@if (Route::has('admin.communications.zip') && auth()->guard('user')->check() && bouncer()->hasPermission('contacts.persons.communications'))
    <script>
        /**
         * Address forms fill city, state and country from a US ZIP code.
         * Works on every address field (contacts, organizations, quotes) and
         * never overwrites what someone already typed.
         */
        (function () {
            var url = @json(route('admin.communications.zip', '00000'));
            var isPostcode = /(\[postcode\]|\.postcode)$/;

            function field(scope, key) {
                return scope.querySelector('[name$="[' + key + ']"], [name$=".' + key + '"]');
            }

            function put(el, value) {
                if (! el || el.value) return;

                el.value = value;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }

            document.addEventListener('input', function (event) {
                var input = event.target;

                if (! input.name || ! isPostcode.test(input.name) || ! /^\d{5}$/.test(input.value.trim())) return;

                var scope = input.closest('form') || input.parentElement;

                fetch(url.replace('00000', input.value.trim()), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                    .then(function (response) { return response.ok ? response.json() : null; })
                    .then(function (place) {
                        if (! place) return;

                        put(field(scope, 'country'), place.country);
                        put(field(scope, 'city'), place.city);

                        // The state list appears once the country is set.
                        setTimeout(function () { put(field(scope, 'state'), place.state); }, 80);
                    })
                    .catch(function () {});
            }, true);
        })();
    </script>
@endif
