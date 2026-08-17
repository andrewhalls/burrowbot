## ADDED Requirements

### Requirement: Winners and claim details shown on the closed occurrence
The system SHALL, once an occurrence closes and winners are drawn, update the originally-posted Discord message to show the drawn winners and mark it as ended, so admins and members reading the original post see the outcome without needing a separate message.

#### Scenario: Original message updated with winners
- **WHEN** an occurrence with a winners section closes and winners are drawn
- **THEN** the system requests the bot update the original posted message's winners section to name the drawn winners and mark the giveaway as ended

#### Scenario: Original message updated with zero winners
- **WHEN** an occurrence closes with zero eligible entrants
- **THEN** the system requests the bot update the original posted message to show no winners and mark the giveaway as ended, without erroring

### Requirement: Separate winner announcement message with claim details
The system SHALL, once an occurrence closes and winners are drawn, post a new Discord message tagging the drawn winners, built from the giveaway's configured congratulations message template with the winners, prize name, claim link, and claim deadline substituted in - and SHALL skip posting this message if the giveaway has no congratulations message template configured.

#### Scenario: Announcement posted for a giveaway with a template configured
- **WHEN** an occurrence whose giveaway has a congratulations message template closes with at least one winner
- **THEN** the system requests the bot post a new message built from that template, tagging the drawn winners

#### Scenario: Announcement skipped for a giveaway with no template configured
- **WHEN** an occurrence whose giveaway has no congratulations message template closes
- **THEN** the system does not request any winner announcement message

#### Scenario: Claim deadline computed relative to announcement time
- **WHEN** a giveaway's claim deadline is configured as a number of hours and its occurrence closes with at least one winner
- **THEN** the claim deadline substituted into the announcement message is that many hours after the announcement is sent

#### Scenario: Announcement skipped with zero winners
- **WHEN** an occurrence closes with zero eligible entrants
- **THEN** the system does not request any winner announcement message, even if a congratulations message template is configured

## MODIFIED Requirements

### Requirement: Posting an occurrence to Discord
The system SHALL post each occurrence to Discord according to its giveaway's posting mode: as a new Discord thread in the configured channel, or as a new plain message in the configured channel, containing the prize item(s), the eligibility restriction (if any), the end time, the giveaway's image (if set), a winners section shown as pending, and an "Enter" control. If the giveaway has a banner image configured, the post SHALL also include it, positioned ahead of the giveaway's own content. An occurrence SHALL NOT be posted before its scheduled time arrives.

#### Scenario: Occurrence posted with prize and restriction visible
- **WHEN** an occurrence is due to be posted
- **THEN** the system requests the bot post the message/thread naming the prize item(s), stating any booster/role restriction, showing the end time, showing a pending winners section, and including an "Enter" control

#### Scenario: Occurrence posted with an image
- **WHEN** an occurrence whose giveaway has an image is due to be posted
- **THEN** the system requests the bot include that image in the posted message/thread

#### Scenario: Occurrence posted with a banner image
- **WHEN** an occurrence whose giveaway has a banner image is due to be posted
- **THEN** the system requests the bot include the banner image in the posted message, positioned ahead of the giveaway's own content

#### Scenario: Occurrence posted without a banner image
- **WHEN** an occurrence whose giveaway has no banner image configured is due to be posted
- **THEN** the system requests the bot post the message/thread without a banner, unaffected by its absence

#### Scenario: Occurrence not yet due is left scheduled
- **WHEN** a `scheduled` occurrence's scheduled post time has not yet arrived
- **THEN** the system does not post it, and it remains `scheduled`
