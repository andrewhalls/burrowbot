<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Actions\Discord\SyncGuildRolesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncGuildRolesRequest;
use App\Models\Guild;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function __construct(private readonly SyncGuildRolesAction $syncRoles) {}

    public function sync(SyncGuildRolesRequest $request, Guild $guild): JsonResponse
    {
        $this->syncRoles->execute($guild, $request->array('roles'));

        return response()->json($guild->roles()->orderBy('name')->get());
    }
}
