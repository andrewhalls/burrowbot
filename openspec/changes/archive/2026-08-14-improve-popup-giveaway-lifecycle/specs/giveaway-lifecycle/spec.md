## ADDED Requirements

### Requirement: Scheduled start
The system SHALL allow a guild admin to set a future date/time when creating a giveaway, instead of starting it immediately, and SHALL automatically start it at that time without requiring further admin action.

#### Scenario: Giveaway created with a scheduled start
- **WHEN** a guild admin creates a giveaway with a future scheduled start date/time instead of starting it immediately
- **THEN** the system creates the giveaway in `draft` state with that scheduled start recorded, and does not post it to Discord yet

#### Scenario: Scheduled start fires automatically
- **WHEN** a draft giveaway's scheduled start time arrives
- **THEN** the system automatically posts it to Discord, transitions it to `active`, and sets `ends_at` to that moment plus the configured duration - identical to a manual start, just without an admin click

#### Scenario: Manual start still available before the scheduled time
- **WHEN** a guild admin manually starts a giveaway that has a future scheduled start time, before that time arrives
- **THEN** the system starts it immediately per the existing "Starting a giveaway" requirement, and the now-moot scheduled start never fires
