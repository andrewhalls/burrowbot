# event-signups Specification

## Purpose

Governs a Discord member choosing a role, or explicitly Not Attending, on a specific event occurrence - enforcing the role's capacity/waitlist behavior and the role set's single-vs-multiple-role policy, and allowing free changes up until the occurrence's scheduled start time.

## Requirements

### Requirement: Signing up for a role with capacity remaining
The system SHALL let a member sign up for any role on an occurrence that has not yet reached its scheduled start time, when that role is uncapped or has remaining capacity.

#### Scenario: Successful role signup
- **WHEN** a member selects a role that is uncapped, or capped with capacity remaining
- **THEN** the system records the member as confirmed for that role on that occurrence

### Requirement: Capped-blocking role rejects signups once full
The system SHALL reject a signup for a capped-blocking role that has no remaining capacity.

#### Scenario: Role full, blocking
- **WHEN** a member attempts to select a capped-blocking role that already has its maximum number of confirmed members
- **THEN** the system rejects the signup and the member is told the role is full

### Requirement: Capped-with-waitlist role queues signups once full
The system SHALL accept a signup for a capped-with-waitlist role that has no remaining capacity by placing the member on that role's waitlist instead of confirming them.

#### Scenario: Role full, waitlisted
- **WHEN** a member selects a capped-with-waitlist role that already has its maximum number of confirmed members
- **THEN** the system accepts the signup, marks it waitlisted, and the member is told they are on the waitlist rather than confirmed

### Requirement: Marking Not Attending clears role signups
The system SHALL, when a member marks Not Attending on an occurrence, remove every role signup (confirmed or waitlisted) that member holds on that occurrence, freeing any capacity and triggering waitlist promotion where applicable.

#### Scenario: Switching to Not Attending while holding a confirmed role
- **WHEN** a member who holds a confirmed role marks Not Attending
- **THEN** their role signup is removed, their role's capacity is freed, and they are recorded as Not Attending

### Requirement: Selecting a role clears Not Attending
The system SHALL, when a member who is marked Not Attending selects a role, clear the Not Attending status and process the role selection normally.

#### Scenario: Switching from Not Attending to a role
- **WHEN** a member marked Not Attending selects a role
- **THEN** their Not Attending status is cleared and the role selection is processed per the capacity/waitlist requirements above

### Requirement: Single-role role sets replace the previous role
The system SHALL, for a role set that does not allow multiple roles, replace a member's existing role signup with their new selection - freeing the previous role's capacity and triggering waitlist promotion where applicable - rather than holding both.

#### Scenario: Changing role under a single-role policy
- **WHEN** a member who holds role A on an occurrence governed by a single-role role set selects role B
- **THEN** their role A signup is removed (freeing its capacity) and a role B signup is created per the capacity/waitlist requirements above

### Requirement: Multiple-role role sets add additional roles
The system SHALL, for a role set that allows multiple roles, add a new role signup for a member alongside their existing role signups on that occurrence, rather than replacing them.

#### Scenario: Adding a second role under a multiple-role policy
- **WHEN** a member who holds role A on an occurrence governed by a multiple-role role set selects role B
- **THEN** the member ends up holding both role A and role B signups on that occurrence

### Requirement: Waitlist promotion on capacity release
The system SHALL, whenever a confirmed role signup is removed (by the member changing roles, marking Not Attending, or cancelling), promote the earliest-waitlisted member for that role - if any - to confirmed status.

#### Scenario: Promotion on a role change
- **WHEN** a confirmed member on a capped-with-waitlist role gives up that role (by switching roles or marking Not Attending) and at least one member is waitlisted for it
- **THEN** the earliest-waitlisted member for that role becomes confirmed, freeing their waitlist position

### Requirement: Freely changing or cancelling a signup before the occurrence starts
The system SHALL allow a member to change their role, switch to/from Not Attending, or clear their response entirely, any number of times, up until the occurrence's scheduled start time.

#### Scenario: Change before start time
- **WHEN** a member changes their signup before the occurrence's scheduled start time
- **THEN** the change is accepted per the requirements above

#### Scenario: Change attempt after start time rejected
- **WHEN** a member attempts to change or cancel their signup after the occurrence's scheduled start time has passed
- **THEN** the system rejects the change and the member's existing signup (or lack thereof) is unaffected
