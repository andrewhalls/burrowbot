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

// Browser-local timezone input + display (openspec specs/browser-local-time).
// Unlike the channel picker above, these need to actively mutate elements
// the moment they appear (set a hidden field's initial value; relabel a
// UTC-timestamp's displayed text) rather than just wait for a user-triggered
// event - plain `document`-level delegation doesn't cover that. So this
// re-scans on both the initial page load and every Livewire morph, using
// Livewire's own `morph.updated` hook (registered once Livewire itself has
// initialized, since `@vite` loads this script before `@livewireScripts` -
// see layout.blade.php).
function applyBrowserLocalTime(root) {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone

    root.querySelectorAll('[data-browser-timezone-input]').forEach((input) => {
        if (input.value === timezone) return
        input.value = timezone
        input.dispatchEvent(new Event('input', { bubbles: true }))
    })

    root.querySelectorAll('[data-utc-datetime]').forEach((el) => {
        const date = new Date(el.dataset.utcDatetime)
        if (Number.isNaN(date.getTime())) return

        el.textContent = date.toLocaleString(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        })
    })
}

document.addEventListener('DOMContentLoaded', () => applyBrowserLocalTime(document))
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', ({ el }) => applyBrowserLocalTime(el))
})

// Livewire uploads a selected file to a dedicated endpoint immediately on
// selection, before any of our own `max:5120` validation ever runs - if
// that raw upload exceeds the server's own upload_max_filesize/
// post_max_size (php.ini) or a reverse proxy's body-size limit, it fails
// with a generic "too large" error with no useful message, since the
// request never reaches Laravel's validation layer at all. Checking the
// file size client-side, before ever attempting the upload, catches the
// common case with an actual explanation instead - though the ceiling
// here can only ever be an approximation of ours (5MB, matching every
// image field's `max:5120` rule); if the real server limit is lower than
// that, only a server-config change (not app code) can raise it.
const MAX_IMAGE_UPLOAD_BYTES = 5 * 1024 * 1024

document.addEventListener(
    'change',
    (event) => {
        const input = event.target
        if (input.tagName !== 'INPUT' || input.type !== 'file' || !input.hasAttribute('wire:model')) return

        const existingError = input.parentElement.querySelector('[data-file-size-error]')
        existingError?.remove()

        const file = input.files?.[0]
        if (!file || file.size <= MAX_IMAGE_UPLOAD_BYTES) return

        input.value = ''

        const message = document.createElement('p')
        message.dataset.fileSizeError = ''
        message.className = 'text-danger text-xs mt-1'
        message.textContent = `That file is too large (${(file.size / (1024 * 1024)).toFixed(1)}MB) - please choose one under 5MB.`
        input.insertAdjacentElement('afterend', message)
    },
    true,
)
