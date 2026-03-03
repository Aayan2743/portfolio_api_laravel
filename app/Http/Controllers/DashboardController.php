<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Project;
use App\Models\ProjectUserAction;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
{
    // 🔹 Cards Data
    $totalProjects = Project::count();
    $totalCategories = Category::count();


    // 🔹 Total Interested Users (unique users)
    $totalInterestedUsers = ProjectUserAction::where('is_interested', true)
        ->distinct('user_id')
        ->count('user_id');

    // 🔹 If you don't have visit system
    $totalVisits = 0;

    // 🔹 Top Projects (based on interested count)
    $topProjects = Project::withCount([
            'interested'
        ])
        ->orderByDesc('interested_count')
        ->take(5)
        ->get()
        ->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title,
                'visits' => 0,
                'interested' => $project->interested_count,
            ];
        });

    return response()->json([
        'status' => true,
        'data' => [
            'cards' => [
                'total_visits' => $totalVisits,
                'interested_users' => $totalInterestedUsers,
                'total_projects' => $totalProjects,
                'categories' => $totalCategories,

            ],
            'top_projects' => $topProjects
        ]
    ]);
}

public function user_index(Request $request)
{
    $query = User::query();

    // 🔍 Search
    if ($request->search) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('phone', 'LIKE', "%{$search}%");
        });
    }

    // 📌 Eager load interested projects
    $users = $query->with([
            'interestedProjects.project:id,title'
        ])
        ->latest()
        ->paginate(10);

    // 🔄 Format response
    $users->getCollection()->transform(function ($user) {

        $interestedProjects = $user->interestedProjects
            ->pluck('project.title')
            ->filter()
            ->values();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'projects_interested' => $interestedProjects,
            'projects_visited' => [] // until visit system
        ];
    });

    return response()->json([
        'status' => true,
        'data' => $users
    ]);
}
}
