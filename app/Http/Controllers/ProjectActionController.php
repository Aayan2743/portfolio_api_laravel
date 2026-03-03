<?php

namespace App\Http\Controllers;

use App\Models\ProjectUserAction;
use Illuminate\Http\Request;

class ProjectActionController extends Controller
{
    public function toggleLike(Request $request, $projectId)
{
    $user = auth()->user();
    $projectId = (int) $projectId; // Ensure it's an integer
    // dd($projectId);

    $action = ProjectUserAction::updateOrCreate(
        [
            'project_id' => $projectId,
            'user_id' => $user->id
        ],
        []
    );

    $action->is_liked = !$action->is_liked;
    $action->save();

    return response()->json([
        'success' => true,
        'liked' => $action->is_liked
    ]);
    }

    public function toggleInterested(Request $request, $projectId)
    {
        $user = auth()->user();

        $action = ProjectUserAction::updateOrCreate(
            [
                'project_id' => $projectId,
                'user_id' => $user->id
            ],
            []
        );

        $action->is_interested = !$action->is_interested;
        $action->save();

        return response()->json([
            'success' => true,
            'interested' => $action->is_interested
        ]);
    }
}
