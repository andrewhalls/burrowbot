## MODIFIED Requirements

### Requirement: Giveaway list view
The system SHALL show a guild admin a list of every giveaway belonging to their guild, with each giveaway's status, entrant count, description, and image (where set), and SHALL let the admin start a `draft` giveaway directly from that list.

#### Scenario: Viewing a guild's giveaways
- **WHEN** a guild admin opens their guild's giveaway list
- **THEN** the system shows every giveaway belonging to that guild - and none belonging to any other guild - each with its current status and entrant count

#### Scenario: Starting a draft from the list
- **WHEN** a guild admin uses the start action on a `draft` giveaway shown in the list
- **THEN** the system starts that giveaway per `giveaway-lifecycle` - "Starting a giveaway", and the list reflects its new `active` status

#### Scenario: Description and image shown when set
- **WHEN** a guild admin opens their guild's giveaway list and a giveaway has a description and/or image
- **THEN** the list shows that giveaway's description and/or image alongside its status and entrant count
