<?php

declare(strict_types=1);

namespace App\Actions\GuildAdmins;

use App\Models\GuildAdmin;

/**
 * Replaces (not appends to) a granted admin's section list.
 *
 * See openspec specs/guild-admin-permissions - "Editing a scoped admin's sections".
 */
class UpdateGuildAdminSectionsAction
{
    /**
     * @param  list<string>  $sections
     */
    public function execute(GuildAdmin $admin, array $sections): GuildAdmin
    {
        $admin->update(['sections' => array_values($sections)]);

        return $admin;
    }
}
