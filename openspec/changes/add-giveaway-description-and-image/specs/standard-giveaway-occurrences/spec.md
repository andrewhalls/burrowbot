## MODIFIED Requirements

### Requirement: Posting an occurrence to Discord
The system SHALL post each occurrence to Discord according to its giveaway's posting mode: as a new Discord thread in the configured channel, or as a new plain message in the configured channel, containing the prize item(s), the eligibility restriction (if any), the end time, the giveaway's image (if set), and an "Enter" control.

#### Scenario: Occurrence posted with prize and restriction visible
- **WHEN** an occurrence is due to be posted
- **THEN** the system requests the bot post the message/thread naming the prize item(s), stating any booster/role restriction, showing the end time, and including an "Enter" control

#### Scenario: Occurrence posted with an image
- **WHEN** an occurrence whose giveaway has an image is due to be posted
- **THEN** the system requests the bot include that image in the posted message/thread
