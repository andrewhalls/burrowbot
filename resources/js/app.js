// Channel picker (resources/views/components/channel-picker.blade.php): a
// plain HTML/JS widget, not a Livewire component (design.md Decision 3,
// add-discord-channel-picker). Lives here - not in an inline <script> inside
// the Blade component - because several of its call sites (CreateGiveaway,
// CreateStandardGiveaway, CreateEvent) are nested Livewire components that
// only ever enter the DOM via an AJAX-toggled morph (`wire:click="$toggle(...)"`),
// and browsers never execute a <script> tag that arrives via DOM injection
// rather than native document parsing. Registering these listeners here
// instead - on `document`, at real page-load time - sidesteps that
// entirely: the listeners exist before the picker markup ever does, so it
// doesn't matter whether that markup is present on first paint or injected
// later by Livewire.
function closestPicker(el) {
    return el.closest('[data-channel-picker]')
}

function filterOptions(picker, query) {
    const needle = query.trim().toLowerCase()
    picker.querySelectorAll('[data-channel-picker-option]').forEach((option) => {
        const matches = needle === '' || option.dataset.name.toLowerCase().includes(needle)
        option.classList.toggle('hidden', !matches)
    })
}

function selectOption(picker, option) {
    const search = picker.querySelector('[data-channel-picker-search]')
    const hidden = picker.querySelector('[data-channel-picker-value]')

    search.value = '#' + option.dataset.name
    hidden.value = option.dataset.id
    hidden.dispatchEvent(new Event('input', { bubbles: true }))

    picker.querySelector('[data-channel-picker-list]').classList.add('hidden')
}

document.addEventListener('input', (event) => {
    if (!event.target.matches('[data-channel-picker-search]')) return
    const picker = closestPicker(event.target)
    filterOptions(picker, event.target.value)
    picker.querySelector('[data-channel-picker-list]').classList.remove('hidden')
})

document.addEventListener('focusin', (event) => {
    if (!event.target.matches('[data-channel-picker-search]')) return
    const picker = closestPicker(event.target)
    filterOptions(picker, event.target.value)
    picker.querySelector('[data-channel-picker-list]').classList.remove('hidden')
})

// mousedown + preventDefault (not click) so the option's selection fires
// before the search input's blur/focusin-elsewhere would otherwise hide the
// list first.
document.addEventListener('mousedown', (event) => {
    const option = event.target.closest('[data-channel-picker-option]')
    if (option) {
        event.preventDefault()
        selectOption(closestPicker(option), option)
        return
    }

    if (!closestPicker(event.target)) {
        document.querySelectorAll('[data-channel-picker-list]').forEach((list) => list.classList.add('hidden'))
    }
})
