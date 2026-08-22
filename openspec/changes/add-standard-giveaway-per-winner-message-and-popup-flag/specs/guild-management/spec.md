## ADDED Requirements

### Requirement: Popup giveaway winner-message feature flag
The system SHALL let a guild admin enable or disable, per guild via Guild Settings, whether the Popup Giveaway per-winner message feature is available for that guild, defaulting to enabled.

#### Scenario: Flag defaults to enabled
- **WHEN** a guild has never had this setting explicitly changed
- **THEN** the popup giveaway per-winner message feature is available for that guild

#### Scenario: Guild admin disables the flag
- **WHEN** a guild admin disables the flag in Guild Settings
- **THEN** the popup giveaway per-winner message feature becomes unavailable for that guild, per `giveaway-lifecycle` and `giveaway-entry`

#### Scenario: Guild admin re-enables the flag
- **WHEN** a guild admin re-enables a previously-disabled flag
- **THEN** the popup giveaway per-winner message feature becomes available again for that guild, using whatever winner-message channel/template are still saved on each giveaway
