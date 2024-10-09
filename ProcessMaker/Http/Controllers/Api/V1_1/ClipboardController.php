<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\V1_1\ClipboardResource as Resource;
use ProcessMaker\Models\Clipboard;

class ClipboardController extends Controller
{
    protected $defaultFields = ['id', 'config'];

    /**
     * Show a specific clipboard by ID
     */
    public function show(int $clipboardId): Resource
    {
        $clipboard = $this->findClipboardOrFail($clipboardId);

        return new Resource($clipboard);
    }

    /**
     * Show clipboard for the authenticated user
     */
    public function showByUserId(): Resource
    {
        $userId = Auth::id();
        $clipboard = Clipboard::where('user_id', $userId)->firstOrFail();

        return new Resource($clipboard);
    }

    public function createOrUpdateForUser(Request $request): \Illuminate\Http\Response
    {
        $userId = Auth::id();
        $data = $request->all();
        $data['user_id'] = $userId;
        // Check if a clipboard already exists for the user
        $clipboard = Clipboard::where('user_id', $userId)->first();

        if ($clipboard) {
            $clipboard->fill($data);
        } else {
            $clipboard = new Clipboard($data);
            $clipboard->fill($data);
        }

        // Save the clipboard (either newly created or updated)
        $clipboard->saveOrFail();

        return response(new Resource($clipboard), 201);
    }

    /**
     * Update an existing clipboard for the authenticated user
     */
    public function update(int $clipboardId, Request $request): \Illuminate\Http\Response
    {
        $clipboard = $this->findClipboardOrFail($clipboardId);
        $this->authorizeUser($clipboard);

        $data = $request->all();
        $data['user_id'] = Auth::id();

        $clipboard->fill($data);
        $clipboard->saveOrFail();

        return response([], 204);
    }

    /**
     * Delete a clipboard for the authenticated user
     */
    public function destroy(int $clipboardId): \Illuminate\Http\Response
    {
        $clipboard = $this->findClipboardOrFail($clipboardId);
        $this->authorizeUser($clipboard);

        $clipboard->delete();

        return response([], 204);
    }

    /**
     * Helper method to find a clipboard by ID or fail
     */
    protected function findClipboardOrFail(int $clipboardId): Clipboard
    {
        return Clipboard::findOrFail($clipboardId);
    }

    /**
     * Helper method to authorize a user for clipboard actions
     */
    protected function authorizeUser(Clipboard $clipboard): void
    {
        if ($clipboard->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
