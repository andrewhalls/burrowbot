# Burrow — AI Coding Guidelines

Burrow is a Discord event management and bot platform. The flagship v1 feature
is the **pop-up giveaway**: a message posted in a Discord channel with a
"Join Giveaway" button; each entrant is instantly assigned a random item from
a collection theme's prize list; the giveaway auto-closes after an admin-set
duration; staff get a searchable/filterable dashboard to hand items out.

This file is the standing brief for any AI agent (or human) implementing
Burrow. The authoritative, detailed source of truth is **not** this file —
it's `openspec/`. Read that first.

## Start here: OpenSpec, not this file, is the spec of record

This repo uses [OpenSpec](https://github.com/Fission-AI/OpenSpec) for
spec-driven development. Before writing or changing any behavior:

1. Run `openspec context` (or read `openspec/config.yaml`) for project-wide
   context and per-artifact rules.
2. Read `openspec/changes/add-discord-giveaway-platform/` — this is the full
   v1 foundation proposal:
   - `proposal.md` — why, what, non-goals, impact
   - `specs/<capability>/spec.md` — the behavior contract per capability
     (`auth`, `guild-management`, `member-directory`, `collection-themes`,
     `giveaway-lifecycle`, `giveaway-entry`, `discord-bot-gateway`,
     `giveaway-admin-dashboard`). Every requirement has WHEN/THEN scenarios —
     treat each scenario as a required test case.
   - `design.md` — the architecture: how Laravel and the bot process talk to
     each other, the data model, the random-assignment algorithm, and how
     giveaway expiry is enforced authoritatively (server-side, not just by
     disabling the Discord button).
   - `tasks.md` — the implementation checklist, in dependency order.
3. `openapi.yaml` (repo root) is the formal contract for the internal
   `/internal/*` API the bot process calls. It documents the **bot-facing**
   API only — the admin dashboard is server-rendered Livewire, not a REST
   consumer, and is not in this document.

**Do not invent requirements or architecture that contradict `openspec/`.**
If something is ambiguous or a real product decision is needed, stop and ask
rather than guessing — see "When to ask vs. decide" below. If you discover
the spec is wrong or incomplete once you're implementing, open a new
OpenSpec change (`/opsx:propose`) rather than silently diverging from it;
don't hand-edit `openspec/specs/` directly except via that workflow.

Implementation work should follow `tasks.md` in order — later groups depend
on earlier ones (e.g. the internal API auth middleware in task group 3 is a
prerequisite for every `/internal/*` endpoint after it).

## Non-negotiable architectural rules

These come straight out of `design.md` and exist to prevent an agent from
"simplifying" its way into a race condition or a security hole:

- **Laravel never calls Discord directly, and the bot never touches MySQL.**
  All Discord I/O goes through the Node.js bot process; all business logic
  and persistence live in Laravel. If you find yourself adding a Discord API
  call inside a Laravel controller/Action, or a DB query inside the bot
  process, stop — that violates the process boundary in `design.md` Decision 1.
- **Giveaway expiry is enforced server-side, on every join request, not just
  by disabling the Discord button.** The button being disabled is a UX nicety
  driven by a scheduled job; the actual guarantee is the `now() >= ends_at`
  check inside the join transaction. Never remove or bypass that check to
  "simplify" the join flow.
- **One entry per member is a database constraint
  (`unique(giveaway_id, discord_member_id)`), not just an application-level
  check.** Concurrent clicks must be handled by catching the constraint
  violation, not by a check-then-insert race.
- **Everything guild-scoped stays guild-scoped.** Any query, policy, or
  Livewire component touching guilds, members, collection themes, giveaways,
  or entries must filter by `guild_id` and must be covered by a cross-guild-denial test.
  There is no global admin flag — authorization is always "admin of guild X."
- **The internal `/internal/*` API is bot-only.** It authenticates via a
  single shared `BOT_SERVICE_TOKEN` bearer token, not per-user auth. Never
  expose it to the dashboard frontend or reuse it as a general-purpose API.

## Tech stack & conventions

- **Backend:** Laravel (latest LTS-track), PHP 8.3+, strict types
  (`declare(strict_types=1);`), MySQL 8.
- **Dashboard:** Livewire + Blade, Tailwind CSS. No separate SPA/API layer
  for dashboard pages — Livewire components query Eloquent directly.
- **Dashboard shell is automatic — never build a page-specific nav/header.**
  Every full-page Livewire component is auto-wrapped in
  `resources/views/components/layout.blade.php` (via
  `config('livewire.component_layout') = 'components.layout'`), which
  renders the sidebar, top bar, guild switcher, and dark/light theme
  (`resources/views/components/dashboard-sidebar.blade.php` and
  `dashboard-topbar.blade.php`). A new page gets this shell for free simply
  by being a normal full-page Livewire component — do not add your own
  header/nav, and do not override the layout (`#[Layout(...)]`) or build a
  page as a plain Blade route, either of which silently bypasses the shell
  (see `openspec/specs/dashboard-home` - "Shell applies automatically to
  every page, present and future"). If a guild-scoped page's `mount()`
  doesn't itself type-hint `Guild $guild` (e.g. it binds a more specific
  child model like an occurrence), the layout still resolves the current
  guild via `Guild::find(request()->route('guild'))` as a fallback — see
  the comment in `layout.blade.php` before changing that logic.
- **Bot process:** Node.js + discord.js, lives in `bot/` as its own package,
  deployed as a separate long-running process from the Laravel app.
- **Auth:** Laravel Socialite (Discord OAuth driver) for dashboard staff.
  No local password accounts.
- **Code shape:** thin controllers/Livewire components; business logic in
  Action/Service classes; validation in Form Requests; authorization in
  Policies; every model ships a factory. Queue anything that calls an
  external system (Discord REST calls via outbound actions, scheduled
  giveaway closing).
- **Style:** PSR-12. No commented-out code, no speculative abstractions —
  build what the current task needs.
- **Migrations targeting compound indexes/unique constraints, or a foreign
  key on a long column/table name, must pass an explicit short name**: e.g.
  `$table->unique(['a', 'b', 'c'], 'short_name')` for indexes, or
  `$table->foreignId('col')->constrained(indexName: 'short_name')` for
  foreign keys. Laravel's auto-generated name (`{table}_{col1}_{col2}_..._
  index` or `..._foreign`) can exceed MySQL's 64-character identifier limit
  on long table/column combinations — SQLite (what the test suite runs on)
  accepts overlong index names silently and doesn't even store foreign-key
  names at all, so this only surfaces once deployed against real MySQL. This
  bit us twice: `event_role_signups`'s 3-column index (72 chars), then
  `standard_giveaway_entries`/`standard_giveaway_winners`'s FK to
  `standard_giveaway_occurrence_id` (65 chars each).
  `tests/Feature/Database/SchemaIdentifierLengthTest.php` now compiles every
  migration against a real MySQL grammar (via `pretend()`, no live server
  needed) and asserts every resulting identifier is ≤64 chars as a standing
  regression guard — keep it passing, and if it's not obvious why an
  identifier is long, run just that test to see the compiled SQL.

## Testing — required, not optional

- **Pest** is the test runner for the Laravel app. Every capability's
  scenarios in `openspec/changes/.../specs/*/spec.md` should map to at least
  one Pest test — that's the acceptance criteria.
- **No mocking the database.** Feature tests hit real Eloquent
  models against SQLite or a MySQL test database. Mock only true externals
  (the Discord REST API from the bot's own test suite; never Laravel's own
  DB from Laravel's own tests).
- **Unit-test pure logic in isolation**, especially the random item
  assignment algorithm (`design.md` §3) — it should be a seedable-RNG pure
  function with dedicated tests for "more items than entrants," "pool
  exactly exhausted," and "pool exhausted, one more entrant."
- **Concurrency-sensitive paths get a concurrency test**: joining a giveaway
  with one remaining unique item, hit from two "simultaneous" requests,
  should never award it twice.
- The bot process gets its own test suite (Jest/Vitest) for the
  interaction-to-reply-text and outbound-action-to-Discord-call mappings,
  using a mocked Discord client — the bot has no business logic to test
  beyond those mappings.
- Every new migration ships with a factory; every new Livewire component
  ships with a Pest/Livewire test, per the rules in `openspec/config.yaml`.

## When to ask vs. decide

Ask the user before proceeding when a choice would change externally
observable behavior, security posture, or data shape that `openspec/` didn't
already pin down (e.g. "should removed guild admins lose access immediately
or on next login" if that's ever genuinely unspecified). For implementation
details already fixed by `design.md` (queue driver specifics, exact poll
interval, internal endpoint shapes) — just follow it; it's already a
decision, not an open question. `design.md`'s own "Open Questions" section
lists the one thing intentionally left flexible (outbound-action transport:
polling now, maybe push later) — that one's fine to leave as polling unless
asked to revisit it.

## Keeping this file and openspec/ in sync

If you add or change a capability, update it through the OpenSpec workflow
(`/opsx:propose`, `/opsx:apply`, `/opsx:archive` — see `.claude/commands/opsx/`
and `.claude/skills/`) rather than editing `openspec/specs/` by hand. Once a
change is archived, its specs become the new source of truth under
`openspec/specs/<capability>/spec.md`; re-read those (not just this file)
before touching a capability that already shipped.
