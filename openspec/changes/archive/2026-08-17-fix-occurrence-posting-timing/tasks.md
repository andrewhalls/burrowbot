## 1. Due-time fix

- [x] 1.1 `PostDueStandardGiveawayOccurrences`: add `where('scheduled_post_at', '<=', now())`
- [x] 1.2 `PostDueEventOccurrences`: add `where('scheduled_start_at', '<=', now())`
- [x] 1.3 Fix existing Pest tests in both `PostDue*OccurrencesTest.php` that relied on a future-scheduled occurrence posting (give them an explicit past scheduled time)
- [x] 1.4 New Pest tests: an occurrence whose scheduled time hasn't arrived is not posted and stays `scheduled` (both domains)

## 2. Widen generation window

- [x] 2.1 `GenerateStandardGiveawayOccurrences::WINDOW_DAYS` and `GenerateEventOccurrences::WINDOW_DAYS`: 30 -> 90
- [x] 2.2 Pest test: a weekly recurring series generates more than 4 occurrences in one run (proves the widened window)

## 3. Occurrence image editing

- [x] 3.1 `UpdateStandardGiveawayOccurrenceAction`: accept `image_path`, delete the old file only if neither the series nor a sibling occurrence still references it (design.md Decision 3)
- [x] 3.2 `EditStandardGiveawayOccurrence`: `WithFileUploads`, `image` property, pass through to the action
- [x] 3.3 `edit-standard-giveaway-occurrence.blade.php`: image field + preview (current or newly-selected)
- [x] 3.4 Pest tests: setting/replacing an occurrence's image; old file deleted once nothing references it, kept while the series or a sibling still does

## 4. Verification

- [x] 4.1 Full Pest suite passes
- [x] 4.2 `npm run build` succeeds
