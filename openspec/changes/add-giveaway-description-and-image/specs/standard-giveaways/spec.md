## MODIFIED Requirements

### Requirement: Standard giveaway creation
The system SHALL allow a guild admin to create a standard giveaway by specifying a title, a description, a Discord channel, an optional image, one or more pre-set prize items, an eligibility restriction, a winner count, a posting mode (new thread per occurrence, or new plain message per occurrence), and either no recurrence (one-off) or a recurrence rule, scoped to their guild.

#### Scenario: Valid one-off standard giveaway created
- **WHEN** a guild admin submits a title, description, channel, at least one prize item, and no recurrence rule
- **THEN** the system creates the standard giveaway and generates exactly one occurrence for it

#### Scenario: Valid recurring standard giveaway created
- **WHEN** a guild admin submits a title, description, channel, at least one prize item, and a recurrence rule with a start time
- **THEN** the system creates the standard giveaway and begins generating occurrences per the recurrence rule

#### Scenario: Missing required fields rejected
- **WHEN** a guild admin submits a standard giveaway without a title, description, or channel
- **THEN** the system rejects the submission with a validation error and creates nothing

#### Scenario: Image is optional
- **WHEN** a guild admin creates a standard giveaway without an image
- **THEN** the system creates it successfully with no image set

### Requirement: Editing a standard giveaway series only affects future occurrences
The system SHALL apply edits to a standard giveaway's title, description, image, channel, prize items, restrictions, winner count, posting mode, or recurrence rule only to occurrences generated after the edit; occurrences already generated keep the values in effect when they were generated.

#### Scenario: Editing prize items of an ongoing recurring giveaway
- **WHEN** a guild admin changes the prize items on a recurring standard giveaway that already has generated occurrences
- **THEN** occurrences generated before the change keep their original prize items, and occurrences generated after the change reference the new ones

#### Scenario: Editing the image of an ongoing recurring giveaway
- **WHEN** a guild admin changes the image on a recurring standard giveaway that already has generated occurrences
- **THEN** occurrences generated before the change keep their original image, and occurrences generated after the change use the new one
