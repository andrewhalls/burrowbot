import 'dotenv/config'
import { Client, Events, GatewayIntentBits } from 'discord.js'
import { createLaravelClient } from './laravelClient.js'
import { createDiscordAdapter, JOIN_GIVEAWAY_BUTTON_ID } from './discordAdapter.js'
import { EVENT_NOT_ATTENDING_PREFIX, EVENT_ROLE_SELECT_PREFIX } from './eventOccurrenceMessage.js'
import { STANDARD_GIVEAWAY_ENTER_PREFIX } from './standardGiveawayOccurrenceMessage.js'
import { createMessageRoutingStore } from './messageRoutingStore.js'
import { startOutboundPoller } from './outboundPoller.js'
import { joinResultReply } from './joinResultReply.js'
import { eventSignupResultReply } from './eventSignupResultReply.js'
import { standardGiveawayEntryResultReply } from './standardGiveawayEntryResultReply.js'

const requiredEnv = ['DISCORD_BOT_TOKEN', 'LARAVEL_BASE_URL', 'BOT_SERVICE_TOKEN']
for (const key of requiredEnv) {
  if (!process.env[key]) {
    console.error(`Missing required environment variable: ${key}`)
    process.exit(1)
  }
}

const laravelClient = createLaravelClient({
  baseUrl: process.env.LARAVEL_BASE_URL,
  serviceToken: process.env.BOT_SERVICE_TOKEN,
})

const routingStore = createMessageRoutingStore()

const client = new Client({ intents: [GatewayIntentBits.Guilds] })

async function upsertObservedMember(guild, user) {
  try {
    await laravelClient.upsertMember(guild.id, user.id, user.username, user.displayAvatarURL())
  } catch (error) {
    console.error(`Failed to sync member ${user.id} in guild ${guild.id}:`, error)
  }
}

client.on(Events.GuildCreate, async (guild) => {
  try {
    await laravelClient.guildJoined(guild.id, guild.name)
    console.log(`Registered guild ${guild.id} (${guild.name})`)
  } catch (error) {
    console.error(`Failed to register guild ${guild.id}:`, error)
  }
})

client.on(Events.GuildDelete, async (guild) => {
  try {
    await laravelClient.updateGuild(guild.id, { is_active: false })
    console.log(`Marked guild ${guild.id} inactive`)
  } catch (error) {
    console.error(`Failed to mark guild ${guild.id} inactive:`, error)
  }
})

client.on(Events.GuildUpdate, async (_oldGuild, newGuild) => {
  try {
    await laravelClient.updateGuild(newGuild.id, { name: newGuild.name })
  } catch (error) {
    console.error(`Failed to update guild ${newGuild.id}:`, error)
  }
})

async function handleEventSignupInteraction(interaction, occurrenceId, eventRoleId) {
  await upsertObservedMember(interaction.guild, interaction.user)

  try {
    const result = await laravelClient.signUpForEventOccurrence(occurrenceId, interaction.user.id, interaction.user.username, eventRoleId)
    await interaction.reply({ content: eventSignupResultReply(result), ephemeral: true })
  } catch (error) {
    console.error(`Failed to process event signup for occurrence ${occurrenceId}:`, error)
    await interaction.reply({ content: 'Something went wrong - please try again.', ephemeral: true })
  }
}

// Discord always includes roles/premium_since on interaction.member, but
// discord.js may resolve it either as a full GuildMember (roles.cache) or a
// raw payload (roles as a plain id array), depending on caching state.
function memberRoleIds(member) {
  if (!member) return []
  if (member.roles?.cache) return [...member.roles.cache.keys()]
  if (Array.isArray(member.roles)) return member.roles

  return []
}

function memberIsBoosting(member) {
  if (!member) return false
  if ('premiumSince' in member) return Boolean(member.premiumSince)

  return Boolean(member.premium_since)
}

async function handleStandardGiveawayEntryInteraction(interaction, occurrenceId) {
  await upsertObservedMember(interaction.guild, interaction.user)

  try {
    const result = await laravelClient.submitStandardGiveawayEntry(
      occurrenceId,
      interaction.user.id,
      interaction.user.username,
      memberRoleIds(interaction.member),
      memberIsBoosting(interaction.member),
    )
    await interaction.reply({ content: standardGiveawayEntryResultReply(result), ephemeral: true })
  } catch (error) {
    console.error(`Failed to process standard giveaway entry for occurrence ${occurrenceId}:`, error)
    await interaction.reply({ content: 'Something went wrong - please try again.', ephemeral: true })
  }
}

client.on(Events.InteractionCreate, async (interaction) => {
  if (interaction.isButton() && interaction.customId === JOIN_GIVEAWAY_BUTTON_ID) {
    await upsertObservedMember(interaction.guild, interaction.user)

    const giveawayId = routingStore.giveawayIdForMessage(interaction.message.id)

    if (!giveawayId) {
      await interaction.reply({ content: 'This giveaway is no longer available.', ephemeral: true })
      return
    }

    try {
      const result = await laravelClient.joinGiveaway(giveawayId, interaction.user.id, interaction.user.username)
      await interaction.reply({ content: joinResultReply(result), ephemeral: true })
    } catch (error) {
      console.error(`Failed to process join for giveaway ${giveawayId}:`, error)
      await interaction.reply({ content: 'Something went wrong - please try again.', ephemeral: true })
    }

    return
  }

  if (interaction.isStringSelectMenu() && interaction.customId.startsWith(EVENT_ROLE_SELECT_PREFIX)) {
    const occurrenceId = interaction.customId.slice(EVENT_ROLE_SELECT_PREFIX.length)
    await handleEventSignupInteraction(interaction, occurrenceId, interaction.values[0])

    return
  }

  if (interaction.isButton() && interaction.customId.startsWith(EVENT_NOT_ATTENDING_PREFIX)) {
    const occurrenceId = interaction.customId.slice(EVENT_NOT_ATTENDING_PREFIX.length)
    await handleEventSignupInteraction(interaction, occurrenceId, null)

    return
  }

  if (interaction.isButton() && interaction.customId.startsWith(STANDARD_GIVEAWAY_ENTER_PREFIX)) {
    const occurrenceId = interaction.customId.slice(STANDARD_GIVEAWAY_ENTER_PREFIX.length)
    await handleStandardGiveawayEntryInteraction(interaction, occurrenceId)
  }
})

client.once(Events.ClientReady, async (readyClient) => {
  console.log(`Logged in as ${readyClient.user.tag}`)

  const restoredCount = await routingStore.rebuildFromLaravel(laravelClient)
  console.log(`Recovered ${restoredCount} active giveaway(s) from Laravel`)

  const adapter = createDiscordAdapter(readyClient)

  startOutboundPoller({
    laravelClient,
    intervalMs: Number(process.env.OUTBOUND_POLL_INTERVAL_MS ?? 3000),
    adapter,
    onMessagePosted: (giveawayId, discordMessageId) => routingStore.remember(discordMessageId, giveawayId),
  })
})

client.on(Events.Error, (error) => console.error('Discord client error:', error))
process.on('unhandledRejection', (error) => console.error('Unhandled rejection:', error))

client.login(process.env.DISCORD_BOT_TOKEN)
