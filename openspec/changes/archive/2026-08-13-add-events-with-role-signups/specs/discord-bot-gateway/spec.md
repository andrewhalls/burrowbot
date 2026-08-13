## ADDED Requirements

### Requirement: Posting an event occurrence
The system SHALL, on request from Laravel, post an event occurrence to the specified Discord channel as either a new thread or a new plain message (per the event's posting mode), containing the event's title, description, scheduled start time, and role-selection controls for every role in the event's role set, and SHALL report the resulting Discord thread or message ID back to Laravel.

#### Scenario: Occurrence posted as a thread
- **WHEN** Laravel requests a thread-mode occurrence be posted
- **THEN** the bot creates a new Discord thread in the configured channel with role-selection controls and reports the thread ID so Laravel can associate it with the occurrence

#### Scenario: Occurrence posted as a message
- **WHEN** Laravel requests a message-mode occurrence be posted
- **THEN** the bot posts a new plain Discord message in the configured channel with role-selection controls and reports the message ID so Laravel can associate it with the occurrence

### Requirement: Relaying event-signup interactions
The system SHALL, on receiving a role-selection or Not-Attending interaction on an occurrence's post, call Laravel's internal API to process the signup and relay Laravel's result back to Discord as a response to that interaction.

#### Scenario: Role selection relayed
- **WHEN** a member selects a role on an occurrence's post
- **THEN** the bot calls Laravel's internal signup endpoint with the occurrence ID, the member's Discord ID, and the selected role, and replies to the Discord interaction with the outcome Laravel returned (confirmed, waitlisted, or rejected)

#### Scenario: Not Attending relayed
- **WHEN** a member selects Not Attending on an occurrence's post
- **THEN** the bot calls Laravel's internal signup endpoint marking that member Not Attending and replies to the Discord interaction with the outcome Laravel returned
