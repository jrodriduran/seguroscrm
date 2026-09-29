{{-- "Communications" shortcut in the header while viewing a contact. --}}
@if (request()->route()?->getName() === 'admin.contacts.persons.view' && bouncer()->hasPermission('contacts.persons.communications') && ($twPersonId = (int) request()->route('id')))
    <a href="{{ route('admin.communications.persons.show', $twPersonId) }}" class="tw-flag max-md:hidden" style="text-decoration: none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
        @lang('communications::app.person.title')
    </a>
@endif
