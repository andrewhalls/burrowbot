/**
 * Filters a guild's roles down to the ones worth syncing (excludes
 * `@everyone` and Discord-managed roles - bot integration roles, the
 * auto-created Server Booster role, already covered by its own dedicated
 * "boosters only" restriction) and maps them to the shape
 * `laravelClient.syncGuildRoles` sends. Pure and side-effect free so it's
 * testable without a live gateway connection - callers pass in
 * `[...guild.roles.cache.values()]`, not the Collection itself.
 *
 * See openspec design.md (add-discord-role-picker) Decision 2.
 *
 * @param {{ id: string, name: string, managed: boolean }[]} roles
 * @param {string} guildId
 * @returns {{ discord_role_id: string, name: string }[]}
 */
export function postableRoles(roles, guildId) {
  return roles
    .filter((role) => role.id !== guildId && !role.managed)
    .map((role) => ({ discord_role_id: role.id, name: role.name }))
}
