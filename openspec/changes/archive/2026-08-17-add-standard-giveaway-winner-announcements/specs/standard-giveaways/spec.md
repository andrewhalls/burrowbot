## ADDED Requirements

### Requirement: Banner image configuration
The system SHALL let a guild admin configure an optional banner (header) image on a standard giveaway series, separate from the giveaway's own prize/item image, that appears at the top of every occurrence posted for that series.

#### Scenario: Series created with a banner image
- **WHEN** a guild admin creates a standard giveaway and uploads a banner image
- **THEN** the giveaway is created with that banner image, distinct from its own item image (if any)

#### Scenario: Banner image is optional
- **WHEN** a guild admin creates or edits a standard giveaway without setting a banner image
- **THEN** the giveaway has no banner image, and occurrences posted for it are unaffected by the absence of one

### Requirement: Winner claim configuration
The system SHALL let a guild admin configure, per standard giveaway series, an optional claim link (a URL or Discord channel reference), an optional claim deadline expressed in hours, and an optional congratulations message template used when winners are announced.

#### Scenario: Claim configuration is optional
- **WHEN** a guild admin creates or edits a standard giveaway without setting a claim link, claim deadline, or congratulations message template
- **THEN** the giveaway is saved successfully with those fields unset

#### Scenario: Claim link accepts a URL or channel reference
- **WHEN** a guild admin sets the claim link to either a URL or a Discord channel reference
- **THEN** the system stores it as configured, without validating it against Discord or the URL's reachability

### Requirement: Congratulations message template placeholders
The system SHALL let a guild admin write a congratulations message template containing placeholders for the drawn winners' mentions, the prize name, the configured claim link, and the computed claim deadline, and SHALL accept a template that uses any subset of these placeholders (including none).

#### Scenario: Template using all placeholders
- **WHEN** a guild admin saves a congratulations message template referencing the winners, prize, claim link, and claim deadline placeholders
- **THEN** the system stores the template as written, substituting all four when a winner announcement is later sent

#### Scenario: Template using no placeholders
- **WHEN** a guild admin saves a congratulations message template with plain text and no placeholders
- **THEN** the system stores and later sends it unchanged, with nothing substituted

## MODIFIED Requirements

### Requirement: Editing a standard giveaway series only affects future occurrences
The system SHALL apply edits to a standard giveaway's title, description, image, banner image, channel, prize items, restrictions, winner count, posting mode, recurrence rule, claim link, claim deadline, or congratulations message template only to occurrences generated after the edit; occurrences already generated keep the values in effect when they were generated.

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
