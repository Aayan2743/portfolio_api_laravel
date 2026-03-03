<?php

namespace App\Http\Controllers;

use App\Services\WebpService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\ProjectFeature;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
         'category_id' => 'required|exists:categories,id',
        'main_heading' => 'required|string',
        'description' => 'required|string',
        'thumbnail_image' => 'required|image|mimes:jpg,jpeg,png',

       'project_image' => 'required|image|mimes:jpg,jpeg,png,webp',

        'features' => 'required|array',
        'features.*.title' => 'required|string',
        'features.*.description' => 'required|string',
        'features.*.image' => 'required|image|mimes:jpg,jpeg,png'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first(),
        ], 422);
    }

    DB::beginTransaction();

    try {

        $data = $request->only([
            'title',
            'category_id',
            'main_heading',
            'description'
        ]);
        // 🔥 Generate Unique Slug
        $slug = Str::slug($request->title);
        $originalSlug = $slug;
        $count = 1;

        while (Project::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $data['slug'] = $slug;

        // Upload Thumbnail
        if ($request->hasFile('thumbnail_image')) {

        $file = $request->file('thumbnail_image');

        // Generate unique name
        $filename = Str::random(20) . '.webp';

        // Full storage path
        $destinationPath = storage_path('app/public/projects/thumbnail/' . $filename);

        // Convert + Resize
        WebpService::convert(
            $file->getRealPath(),
            $destinationPath,
            70,      // quality
            600,     // width
            400      // height
        );

        // Save relative path in DB
        $data['thumbnail_image'] = 'projects/thumbnail/' . $filename;
            }

        $project = Project::create($data);

        // Save Single Images
if ($request->hasFile('project_image')) {

    $file = $request->file('project_image');

    $filename = Str::random(20) . '.webp';

    $destinationPath = storage_path('app/public/projects/images/' . $filename);

    WebpService::convert(
        $file->getRealPath(),
        $destinationPath,
        70,
        1200,
        800
    );

    $project->update([
        'project_image' => 'projects/images/' . $filename
    ]);
}

        // Save Features
        if ($request->features) {
            foreach ($request->features as $key => $feature) {

                $imagePath = null;

                if ($request->hasFile("features.$key.image")) {

    $file = $request->file("features.$key.image");

    // Generate unique filename
    $filename = Str::random(20) . '.webp';

    // Full storage path
    $destinationPath = storage_path('app/public/projects/features/' . $filename);

    // Convert + Resize (recommended for feature icons)
    WebpService::convert(
        $file->getRealPath(),
        $destinationPath,
        70,    // quality
        400,   // width
        400    // height
    );

    // Save relative path
    $imagePath = 'projects/features/' . $filename;
                    }

                ProjectFeature::create([
                    'project_id' => $project->id,
                    'title' => $feature['title'],
                    'description' => $feature['description'] ?? null,
                    'image' => $imagePath
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Project Created Successfully'
        ]);

    } catch (\Exception $e) {

        DB::rollback();

        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
    }

    public function index(Request $request)
{
    $query = Project::with([ 'features']);

    // 🔎 Search by title OR category
   if ($request->search) {
    $query->where('title', 'LIKE', '%' . $request->search . '%')
          ->orWhereHas('category', function ($q) use ($request) {
              $q->where('name', 'LIKE', '%' . $request->search . '%');
          });
}

    $projects = $query->latest()->paginate(10);

    return response()->json([
        'status' => true,
        'data' => $projects
    ]);
}

public function show($id)
{
    $project = Project::with(['images', 'features'])->find($id);

    if (!$project) {
        return response()->json([
            'status' => false,
            'message' => 'Project Not Found'
        ], 404);
    }

    return response()->json([
        'status' => true,
        'data' => $project
    ]);
}

public function update(Request $request, $id)
{
    $project = Project::with('features')->find($id);


    if (!$project) {
        return response()->json([
            'status' => false,
            'message' => 'Project Not Found'
        ], 404);
    }

    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
        'category_id' => 'required|exists:categories,id',
        'main_heading' => 'required|string',
        'description' => 'required|string',
        'thumbnail_image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
        'project_image' => 'nullable|image|mimes:jpg,jpeg,png,webp',

        'features' => 'nullable|array',
        'features.*.title' => 'required|string',
        'features.*.description' => 'required|string',
        'features.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first(),
        ], 422);
    }

    DB::beginTransaction();

    try {

        // ✅ Update Basic Fields
        $project->update([
            'title' => $request->title,
            'category_id' => $request->category_id,
            'main_heading' => $request->main_heading,
            'description' => $request->description,
        ]);


        if ($project->wasChanged('title')) {

    $slug = Str::slug($request->title);
    $originalSlug = $slug;
    $count = 1;

    while (
        Project::where('slug', $slug)
            ->where('id', '!=', $project->id)
            ->exists()
    ) {
        $slug = $originalSlug . '-' . $count++;
    }

    $project->slug = $slug;
    $project->save();
}

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Replace Thumbnail
        |--------------------------------------------------------------------------
        */
        if ($request->hasFile('thumbnail_image')) {

            if ($project->thumbnail_image) {
                Storage::disk('public')->delete($project->thumbnail_image);
            }

            $file = $request->file('thumbnail_image');
            $filename = Str::random(20) . '.webp';
            $destinationPath = storage_path('app/public/projects/thumbnail/' . $filename);

            WebpService::convert(
                $file->getRealPath(),
                $destinationPath,
                70,
                600,
                400
            );

            $project->update([
                'thumbnail_image' => 'projects/thumbnail/' . $filename
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Replace Project Image
        |--------------------------------------------------------------------------
        */
        if ($request->hasFile('project_image')) {

            if ($project->project_image) {
                Storage::disk('public')->delete($project->project_image);
            }

            $file = $request->file('project_image');
            $filename = Str::random(20) . '.webp';
            $destinationPath = storage_path('app/public/projects/images/' . $filename);

            WebpService::convert(
                $file->getRealPath(),
                $destinationPath,
                70,
                1200,
                800
            );

            $project->update([
                'project_image' => 'projects/images/' . $filename
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Replace Features (Optional Strategy)
        |--------------------------------------------------------------------------
        */

        if ($request->features) {

            // 🔥 Delete old features + images
            foreach ($project->features as $oldFeature) {
                if ($oldFeature->image) {
                    Storage::disk('public')->delete($oldFeature->image);
                }
            }

            $project->features()->delete();

            // 🔥 Insert new features
            foreach ($request->features as $key => $feature) {

                $imagePath = null;

                if ($request->hasFile("features.$key.image")) {

                    $file = $request->file("features.$key.image");
                    $filename = Str::random(20) . '.webp';
                    $destinationPath = storage_path('app/public/projects/features/' . $filename);

                    WebpService::convert(
                        $file->getRealPath(),
                        $destinationPath,
                        70,
                        400,
                        400
                    );

                    $imagePath = 'projects/features/' . $filename;
                }

                ProjectFeature::create([
                    'project_id' => $project->id,
                    'title' => $feature['title'],
                    'description' => $feature['description'],
                    'image' => $imagePath
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Project Updated Successfully'
        ]);

    } catch (\Exception $e) {

        DB::rollback();

        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

public function destroy($id)
{
    $project = Project::with('features')->find($id);

    if (!$project) {
        return response()->json([
            'status' => false,
            'message' => 'Project Not Found'
        ], 404);
    }

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Delete Thumbnail
        |--------------------------------------------------------------------------
        */
        if ($project->thumbnail_image) {
            Storage::disk('public')->delete($project->thumbnail_image);
        }

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Delete Project Image
        |--------------------------------------------------------------------------
        */
        if ($project->project_image) {
            Storage::disk('public')->delete($project->project_image);
        }

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Delete Feature Images
        |--------------------------------------------------------------------------
        */
        foreach ($project->features as $feature) {
            if ($feature->image) {
                Storage::disk('public')->delete($feature->image);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ Delete Features Records
        |--------------------------------------------------------------------------
        */
        $project->features()->delete();

        /*
        |--------------------------------------------------------------------------
        | 5️⃣ Delete Project
        |--------------------------------------------------------------------------
        */
        $project->delete(); // soft delete if using SoftDeletes

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Project Deleted Successfully'
        ]);

    } catch (\Exception $e) {

        DB::rollback();

        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


// public

public function getProjectss(Request $request)
{
    $query = Project::with(['features', 'category','actions.user']);

    if ($request->search) {

        $search = $request->search;

        $query->where(function ($q) use ($search) {

            // 🔹 Search by project title
            $q->where('title', 'LIKE', "%{$search}%")

              // 🔹 Search by project slug
              ->orWhere('slug', 'LIKE', "%{$search}%")

              // 🔹 Search by category slug
              ->orWhereHas('category', function ($q2) use ($search) {
                  $q2->where('slug', 'LIKE', "%{$search}%")
                     ->orWhere('name', 'LIKE', "%{$search}%");
              });

        });
    }

    $projects = $query->latest()->paginate(10);

    return response()->json([
        'status' => true,
        'data' => $projects
    ]);
}

public function getProjects(Request $request)
{
    $query = Project::with(['features', 'category'])
        ->withCount([
            'likes',
            'interested'
        ]);

    if ($request->search) {

        $search = $request->search;

        $query->where(function ($q) use ($search) {

            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('slug', 'LIKE', "%{$search}%")
              ->orWhereHas('category', function ($q2) use ($search) {
                  $q2->where('slug', 'LIKE', "%{$search}%")
                     ->orWhere('name', 'LIKE', "%{$search}%");
              });

        });
    }

    $projects = $query->latest()->paginate(10);

    return response()->json([
        'status' => true,
        'data' => $projects
    ]);
}
}
