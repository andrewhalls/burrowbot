## MODIFIED Requirements

### Requirement: Posting an occurrence to Discord
The system SHALL post each occurrence to Discord according to its giveaway's posting mode: as a new Discord thread in the configured channel, or as a new plain message in the configured channel, containing the prize item(s), the eligibility restriction (if any), the end time, the giveaway's image (if set), and an "Enter" control. An occurrence SHALL NOT be posted before its scheduled time arrives.

#### Scenario: Occurrence posted with prize and restriction visible
- **WHEN** an occurrence is due to be posted
- **THEN** the system requests the bot post the message/thread naming the prize item(s), stating any booster/role restriction, showing the end time, and including an "Enter" control

#### Scenario: Occurrence posted with an image
- **WHEN** an occurrence whose giveaway has an image is due to be posted
- **THEN** the system requests the bot include that image in the posted message/thread

#### Scenario: Occurrence not yet due is left scheduled
- **WHEN** a `scheduled` occurrence's scheduled post time has not yet arrived
- **THEN** the system does not post it, and it remains `scheduled`

### Requirement: Editing a single upcoming occurrence
The system SHALL let a guild admin edit a single `scheduled` occurrence's description, prize items, and image, independent of its series and every other occurrence, and SHALL reject editing once that occurrence is no longer `scheduled`.

#### Scenario: Editing a scheduled occurrence's description and prize items
- **WHEN** a guild admin edits a `scheduled` occurrence's description and/or prize items
- **THEN** the system saves the change against that occurrence only - the series' own template and every other occurrence (already generated or generated later) are unaffected

#### Scenario: Editing a scheduled occurrence's image
- **WHEN** a guild admin uploads a new image for a `scheduled` occurrence
- **THEN** the system records that image against that occurrence only

#### Scenario: Editing rejected once posted
- **WHEN** a guild admin attempts to edit an occurrence that is `posted` or `closed`
- **THEN** the system rejects the edit, so what already went to Discord for that occurrence never changes after the fact

#### Scenario: Browsing upcoming occurrences to edit
- **WHEN** a guild admin views a standard giveaway series with one or more `scheduled` occurrences
- **THEN** the system shows those upcoming occurrences, each reachable for editing
