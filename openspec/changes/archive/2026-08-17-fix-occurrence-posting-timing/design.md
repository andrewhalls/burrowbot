## Context

Both `PostDueEventOccurrences` and `PostDueStandardGiveawayOccurrences` query `where('status', 'scheduled')` with no time condition, then post everything returned - discovered while investigating why "pre-fill several future weeks in advance" wouldn't behave as expected (see proposal.md - Why). Both main specs already phrase their posting requirement in terms of an occurrence being "due" - this fix makes that condition real rather than implicit-and-unenforced.

## Goals / Non-Goals

**Goals:**
- An occurrence only posts once its scheduled time has arrived.
- Enough future occurrences exist at once that "plan the next several weeks" is actually possible.

**Non-Goals:**
- A configurable lead time (post N minutes/hours before the scheduled time) - not requested; "post at the scheduled time" is the bar to clear here, not "post early on purpose."
- Retroactively correcting any occurrence that already posted early - this environment can't inspect production data, and the fix prevents it going forward regardless of past state.

## Decisions

### Decision 1: Compare against `now()` directly, no grace window
`->where('scheduled_post_at', '<=', now())` / `->where('scheduled_start_at', '<=', now())`. Both commands already run every minute (`routes/console.php`), so an occurrence posts within a minute of its scheduled time either way - a grace window would only matter if the schedule were coarser, which it isn't.

### Decision 2: Widen the generation window to 90 days, not further
90 days covers ~12-13 occurrences for a weekly cadence (comfortably past the "6 weeks" that prompted this) and ~90 for daily - a larger number of pre-created rows than today, but each is a small model already `firstOrCreate`-deduplicated by `(series_id, scheduled_time)`, and now that Decision 1 stops them all posting at once, sitting in `scheduled` status for months is exactly the intended state, not a problem. Went with 90 over an even larger number (e.g. 365) to keep the pre-generation batch size and the "how far can I currently plan ahead" answer both reasonable without a stated need for a full year yet.

### Decision 3: Occurrence image edits reuse the same orphan-safe delete pattern as the series-level image edit
`UpdateStandardGiveawayOccurrenceAction` deletes the previous `image_path`'s file only if nothing else still points at it - checked against both the series' own current `image_path` (an occurrence usually still shares the series' image until explicitly overridden) and every sibling occurrence. Same shape as `UpdateStandardGiveawayAction`'s existing image-replace logic, applied one level down.

## Risks / Trade-offs

- [Any series with a recurring rule and no occurrences posted yet may have occurrences that, under the old code, would have posted immediately on the next `generate` + `post-due` cycle - after this fix they correctly wait] → This is the intended correction, not a side effect to mitigate; flagged in proposal.md's Impact so the user is aware of the behavior change on already-live series rather than surprised by it.
- [Existing `PostDue*` Pest tests create occurrences with a *future* scheduled time by factory default and assert they post - true today only because of the bug] → Corrected as part of this change: those tests get an explicit past time (they're testing "posts when due"), and new tests cover "does not post when not yet due" using the unmodified future-leaning factory default.
