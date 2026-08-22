## ADDED Requirements

### Requirement: Per-winner message configuration
The system SHALL let a guild admin configure, per standard giveaway series, an optional Discord channel and an optional mail-merge message template used to send an individual message to each drawn winner when an occurrence closes - distinct from and independent of the series' existing congratulations message channel/template (`Winner claim configuration`), which sends one combined message naming all winners together. The system SHALL require both fields to be set together: configuring one without the other SHALL be rejected by validation.

#### Scenario: Both fields configured together
- **WHEN** a guild admin sets both a per-winner message channel and a per-winner message template on a standard giveaway
- **THEN** the system saves both

#### Scenario: Neither field configured
- **WHEN** a guild admin creates or edits a standard giveaway without setting a per-winner message channel or template
- **THEN** the giveaway is saved successfully with both left unset

#### Scenario: Setting only one of the pair is rejected
- **WHEN** a guild admin submits a per-winner message channel without a template, or a template without a channel
- **THEN** the system rejects the submission with a validation error and saves neither

#### Scenario: Independent of the existing congratulations message
- **WHEN** a guild admin configures the per-winner message channel/template on a series that also has the existing congratulations message channel/template configured
- **THEN** the system saves both mechanisms independently, neither depending on nor overriding the other

## MODIFIED Requirements

### Requirement: Editing a standard giveaway series only affects future occurrences
The system SHALL apply edits to a standard giveaway's title, description, image, banner image, channel, prize items, restrictions, winner count, posting mode, recurrence rule, claim link, claim deadline, congratulations message template, per-winner message channel, or per-winner message template only to occurrences generated after the edit; occurrences already generated keep the values in effect when they were generated.

#### Scenario: Editing prize items of an ongoing recurring giveaway
- **WHEN** a guild admin changes the prize items on a recurring standard giveaway that already has generated occurrences
- **THEN** occurrences generated before the change keep their original prize items, and occurrences generated after the change reference the new ones

#### Scenario: Editing the image of an ongoing recurring giveaway
- **WHEN** a guild admin changes the image on a recurring standard giveaway that already has generated occurrences
- **THEN** occurrences generated before the change keep their original image, and occurrences generated after the change use the new one

#### Scenario: Editing the banner image of an ongoing recurring giveaway
- **WHEN** a guild admin changes the banner image on a recurring standard giveaway that already has generated occurrences
- **THEN** occurrences generated before the change keep their original banner image (or lack thereof), and occurrences generated after the change use the new one

#### Scenario: Editing the congratulations message template of an ongoing recurring giveaway
- **WHEN** a guild admin changes the congratulations message template on a recurring standard giveaway that already has generated occurrences
- **THEN** occurrences generated before the change use the template in effect when they were generated, and occurrences generated after the change use the new one

#### Scenario: Editing the per-winner message fields of an ongoing recurring giveaway
- **WHEN** a guild admin changes the per-winner message channel or template on a recurring standard giveaway that already has generated occurrences
- **THEN** occurrences generated before the change use the per-winner message configuration in effect when they were generated, and occurrences generated after the change use the new one
