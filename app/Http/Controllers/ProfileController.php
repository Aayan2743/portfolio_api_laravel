<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\WebpService;

class ProfileController extends Controller
{
    public function update(Request $request)
{
    $user = auth()->user();

    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'phone' => 'required|digits:10|unique:users,phone,' . $user->id,
        'password' => 'nullable|min:6',
        'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    DB::beginTransaction();

    try {

        // ✅ Update Basic Info
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        // ✅ Update Password (only if provided)
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        /*
        |--------------------------------------------------------------------------
        | Avatar Update (WebP)
        |--------------------------------------------------------------------------
        */
        if ($request->hasFile('avatar')) {

            // Delete old avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $file = $request->file('avatar');
            $filename = Str::random(20) . '.webp';
            $destinationPath = storage_path('app/public/users/avatar/' . $filename);

            WebpService::convert(
                $file->getRealPath(),
                $destinationPath,
                70,
                400,
                400
            );

            $user->avatar = 'users/avatar/' . $filename;
        }

        $user->save();

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Profile Updated Successfully'
        ]);

    } catch (\Exception $e) {

        DB::rollback();

        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
    }

    public function getProfile(Request $request)
    {
        $user = auth()->user();

        return response()->json([
            'status' => true,
            'data' => $user
        ]);
    }
}
