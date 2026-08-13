## Why

Giveaways cover one kind of Discord event; server staff also need to run recurring activities that require people to commit to a *role* (raid night needs a Tank, healers, DPS; a game night needs drivers vs. spectators) rather than a random prize, and to see who's actually coming. Today that's a spreadsheet or a manually-maintained message. Burrow should let staff define a reusable set of signup roles, attach it to an event, and have Discord itself be the signup sheet - whether the event happens once or on a recurring schedule.

## What Changes

- Add **event role sets**: a reusable, guild-scoped, named collection of roles (e.g. "Raid Roles": Tank, Healer, DPS). Each role independently configured as uncapped, capped (blocking once full), or capped-with-waitlist. A role set also declares whether a member may hold more than one of its roles at once on the same occurrence.
- Add **events**: a title, description, Discord channel, an event role set, a posting mode (new Discord thread per occurrence, or new plain message per occurrence), and either no recurrence (one-off) or a full custom recurrence rule (RFC 5545 RRULE-style: daily/weekly/monthly/every-N/specific days, with an end date or occurrence count).
- Add **event occurrences**: the generated, schedulable instances of an event - one for a one-off event, one per recurrence for a recurring event - each with its own scheduled start time, its own independent signup roster (no carryover between occurrences), and its own Discord post (thread or message per the event's posting mode).
- Add **event signups**: a member choosing a role (or explicitly "Not Attending") on a specific occurrence. Selecting "Not Attending" clears any role signups; selecting a role clears "Not Attending". Role sets that disallow multiple roles replace the member's previous role choice; role sets that allow multiple roles add to it. Capacity and waitlist rules from the role set are enforced at signup time, with FIFO waitlist promotion when a slot opens. Members may change or cancel their signup freely until the occurrence's scheduled start time.
- Extend the **discord-bot-gateway** contract: posting an occurrence (as a thread or a message, each with role-selection controls), and relaying role-signup / not-attending / change-signup interactions to Laravel's internal API.

## Capabilities

### New Capabilities
- `event-role-sets`: reusable, guild-scoped signup role definitions with per-role capacity/waitlist configuration.
- `events`: the title/description/channel/role-set/posting-mode/recurrence definition of an event series.
- `event-occurrences`: generating, scheduling, and posting individual instances of an event, each with an independent roster.
- `event-signups`: a member's role (or Not Attending) choice on a specific occurrence, including capacity enforcement, waitlist promotion, and free changes up to the occurrence's start.

### Modified Capabilities
- `discord-bot-gateway`: add posting an event occurrence (thread or message) and relaying event-signup interactions, alongside the existing giveaway-posting/relaying behavior.

## Non-goals

- No calendar/ICS export or external calendar sync in v1 - signups and schedules live only in Discord and the dashboard.
- No reminder notifications (e.g. "starting in 1 hour") in v1.
- No cross-event dependencies (e.g. "can't sign up for Event B if attending Event A at the same time") in v1.
- No editing of an occurrence's role set or scheduled start time once it has been posted to Discord - editing the parent event only affects occurrences generated afterward.
- No changing/cancelling a signup after an occurrence's scheduled start time has passed in v1.
- Event role sets are a distinct resource from giveaway collection themes (different shape - roles carry capacity/waitlist config, theme items don't) - v1 does not unify them.

## Impact

- New MySQL schema: `event_role_sets`, `event_roles`, `events`, `event_occurrences`, `event_attendances`, `event_role_signups`.
- New dependency: an RFC 5545 RRULE-compatible recurrence library for PHP (e.g. `simshaun/recurr`) to store and expand recurrence rules into concrete occurrence start times.
- New scheduled job: generates upcoming occurrences for active recurring events within a rolling window and posts them to Discord.
- Extends the internal bot API (`openapi.yaml`) with occurrence-posting outbound actions and signup/interaction endpoints, and extends the bot process with the corresponding handlers.
- New guild-scoped dashboard screens: manage role sets, create/edit events, view an occurrence's roster (parallels the existing giveaway dashboard).
