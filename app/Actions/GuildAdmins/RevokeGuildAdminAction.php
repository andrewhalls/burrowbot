<?php

declare(strict_types=1);

namespace App\Actions\GuildAdmins;

use App\Models\GuildAdmin;
use InvalidArgumentException;

/**
 * Permanently revokes a granted (scoped) admin's access. A full
 * (Discord-synced) admin cannot be revoked from here - their access is
 * Discord-derived and can only be changed by changing their Discord
 * permissions.
 *
 * See openspec specs/guild-admin-permissions - "Revoking a scoped admin",
 * "Discord-synced admins cannot be revoked from the dashboard".
 */
class RevokeGuildAdminAction
{
    public function execute(GuildAdmin $admin): void
    {
        if ($admin->source !== GuildAdmin::SOURCE_GRANTED) {
            throw new InvalidArgumentException('Only a granted (scoped) admin can be revoked from the dashboard.');
        }

        $admin->delete();
    }
}
