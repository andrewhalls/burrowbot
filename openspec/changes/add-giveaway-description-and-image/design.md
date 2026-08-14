## Context

See proposal.md - Why. Relevant existing pieces this design builds on:

- No file-upload feature exists anywhere in this app yet - `config/filesystems.php`'s `public` disk is configured (Laravel default) and `deploy.sh` already runs `storage:link`, but nothing uses it. This is genuinely new ground, not an extension of an existing pattern.
- `StandardGiveawayOccurrence` already snapshots every series-level field (`title`, `description`, `channel_id`, etc.) at generation time so an edit never retroactively changes an already-generated occurrence (`standard-giveaway-occurrences` spec). The image field follows this exact pattern - no new mechanism.
- The Laravel <-> bot contract (per `discord-bot-gateway`): Laravel enqueues a `DiscordOutboundAction` row with a `payload` JSON blob; the bot polls, executes, acks. Both `postGiveawayMessage` and `postStandardGiveawayThread`/`Message`'s payloads are plain JSON built in Laravel and consumed as-is by the bot's `discordAdapter.js`/`standardGiveawayOccurrenceMessage.js` - adding `image_url` (and, for Popup Giveaway, `description`) is just new keys in that same existing payload shape, not a new endpoint or mechanism.

## Goals / Non-Goals

**Goals:**
- Staff can attach a description (Popup Giveaway) and an image (both types) when creating a giveaway.
- Both appear on the actual Discord post.
- Standard Giveaway occurrences snapshot the image exactly like every other field.

**Non-Goals:**
- No image editing/cropping (proposal.md).
- No cloud storage integration - local `public` disk only, matching the zero other-storage-provider footprint this app has today. Revisit only if the app ever needs multi-server file serving (it doesn't today - `deploy.sh` targets a single server).

## Decisions

### Decision 1: Images live on the local `public` disk; the DB stores the relative path, not a URL
`Storage::disk('public')->putFile('giveaway-images', $upload)` (Popup Giveaway) / `'standard-giveaway-images'` (Standard Giveaway) returns a relative path (e.g. `giveaway-images/abc123.jpg`), stored in a new `image_path` column. A `getImageUrlAttribute()` accessor (or equivalent) computes `Storage::disk('public')->url($this->image_path)` on demand - the DB never stores a full URL, so the app's domain can change without a data migration. The full URL is what actually goes into the outbound-action payload the bot receives (the bot never needs to know about Laravel's storage disk, only a fetchable HTTPS URL, consistent with how it already receives fully-formed data for everything else).

**Alternative considered**: store the full public URL directly in the DB. Rejected - ties stored data to today's `APP_URL`; the relative-path-plus-accessor approach is standard Laravel practice and costs nothing extra.

### Decision 2: Replacing or clearing an image deletes the old file from disk
When a new image is uploaded to replace an existing one (Standard Giveaway edit), or when saving with the old image cleared, the previous file at the old `image_path` is deleted via `Storage::disk('public')->delete()` before/after the new path is saved. Popup Giveaway has no edit path (proposal.md - Non-goals), so this only matters for Standard Giveaway's existing edit action.

**Alternative considered**: leave orphaned files on disk indefinitely. Rejected - trivial to avoid, and unbounded accumulation of unreferenced image files is exactly the kind of thing that quietly becomes a real disk-usage problem on a single-server deploy.

### Decision 3: Popup Giveaway's description replaces the default instructional line when set, rather than appending to it
Today's fixed line ("Click **Join Giveaway** to enter and instantly find out what you won.") remains the default for a giveaway with no description, so existing/未-described giveaways look exactly as they do today. When a description is set, it fully replaces that line - the admin owns the whole message body, rather than a custom description being awkwardly sandwiched around fixed boilerplate they didn't write.

**Alternative considered**: always show the custom description *and* the instructional line. Rejected - most giveaway descriptions would naturally already explain how to enter; forcing the canned line alongside a custom one reads as redundant.

### Decision 4: Standard Giveaway occurrence generation snapshots `image_path`, exactly like every other field
`standard-giveaways:generate-occurrences` already copies the parent series' current scalar fields into each new occurrence row at generation time (`StandardGiveawayOccurrenceFactory::fromGiveaway()`'s production-code equivalent). `image_path` is added to that same copy list - no new snapshot mechanism, no new edge case beyond what already exists for `title`/`description`/`channel_id`.

## Risks / Trade-offs

- **[Risk]** Uploading a large image could slow down the create-giveaway form or consume significant storage over time. → **Mitigation**: standard Livewire file-upload validation (`image`, `max:5120` KB, common image mimes) rejects oversized/wrong-type files before they're ever stored.
- **[Risk]** A single-server local disk doesn't survive a server rebuild without a separate backup step. → **Mitigation**: accepted trade-off (Non-Goals) - matches this app's existing single-server deployment model; revisit only if that model changes.

## Migration Plan

Three additive migrations: nullable `description` + `image_path` on `giveaways`; nullable `image_path` on `standard_giveaways`; nullable `image_path` on `standard_giveaway_occurrences`. No backfill - existing giveaways simply have none of these set, which is indistinguishable from today's behavior (Decision 3's default-instructional-line fallback covers the Popup Giveaway description gap; a missing image is already a valid "no image" state for both types).
