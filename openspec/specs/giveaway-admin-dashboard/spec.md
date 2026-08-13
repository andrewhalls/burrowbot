# giveaway-admin-dashboard Specification

## Purpose

Gives guild staff a screen, per giveaway, to search and filter entrants by member and track which prizes have been physically/digitally handed out, so fulfilment is easy even for large entrant lists.

## Requirements

### Requirement: Entrant list for a giveaway
The system SHALL display, for a single giveaway, every entrant with their Discord username, assigned item, entry timestamp, and fulfilment status.

#### Scenario: Viewing a giveaway's entrants
- **WHEN** an authorized staff member opens a giveaway's dashboard screen
- **THEN** the system shows all entries for that giveaway only, each with the entrant's username, assigned item, entry time, and whether it has been marked fulfilled

### Requirement: Search entrants by member
The system SHALL let staff filter the entrant list by typing part of a member's username or Discord ID.

#### Scenario: Live search narrows results
- **WHEN** staff type a partial username into the search field on a giveaway's dashboard
- **THEN** the displayed entrant list updates to only entries whose member matches the search text

### Requirement: Filter by prize item or fulfilment status
The system SHALL let staff filter the entrant list by which item was won and by whether the entry has been fulfilled.

#### Scenario: Filter by item
- **WHEN** staff select a specific prize item as a filter
- **THEN** only entrants who were assigned that item are shown

#### Scenario: Filter by unfulfilled
- **WHEN** staff select "not yet handed out" as a filter
- **THEN** only entries not yet marked fulfilled are shown

### Requirement: Mark an entry fulfilled
The system SHALL let staff mark an individual entry as fulfilled (handed out), recording when and by whom.

#### Scenario: Marking fulfilled
- **WHEN** staff mark an entrant's prize as handed out
- **THEN** the system records that entry as fulfilled with a timestamp and the acting staff member, and it is reflected immediately in the entrant list and its filters

### Requirement: Guild-scoped dashboard access
The system SHALL only allow staff authorized as admins of a giveaway's guild to view or act on that giveaway's dashboard.

#### Scenario: Unauthorized access attempt
- **WHEN** a user who is not an admin of a giveaway's guild requests that giveaway's dashboard
- **THEN** the system denies access with a 403 response
