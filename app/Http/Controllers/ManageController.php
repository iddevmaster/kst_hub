<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use function Laravel\Prompts\alert;
use App\Models\agency;
use App\Models\branch;
use App\Models\course;
use App\Models\department;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Activitylog;
use App\Models\quiz;
use Auth;

class ManageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $agns = agency::all();
        $brns = branch::all();
        $dpms = department::all();
        $roles = Role::all();
        $courses = course::all();
        $permissions = Permission::all();

        Log::channel('activity')->info('User '. $request->user()->name .' visited manage',
        [
            'user' => $request->user(),
        ]);
        if ($request->user()->hasAnyRole('admin', 'staff')) {
            return view("page.manages.manage", compact("agns","brns","dpms", "roles", "permissions", "courses"));
        } else {
            Auth::logout();
            return redirect('/');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function createAgency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'address' => 'max:255',
            'contact' => 'max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $validatedData = $validator->validated();
            $agn = new agency;
            $agn->name = $validatedData['name'];
            $agn->address = $validatedData['address'];
            $agn->contact = $validatedData['contact'];
            $agn->save();

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Agency',
                'content' => $agn->id,
                'note' => 'store',
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' create Agency',
            [
                'user' => $request->user(),
                'agn_created' => $agn,
            ]);
            return response()->json(['success' => $agn]);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'ee '.$th->getMessage()]);
        }
    }

    public function createBranch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'agency' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $validatedData = $validator->validated();
            $name = $validatedData['name'];
            $agency = $validatedData['agency'];
            $brn = new branch;
            $brn->name = $name;
            $brn->agency = $agency;
            $brn->save();

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Branch',
                'content' => $brn->id,
                'note' => 'store',
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' create Branch',
            [
                'user' => $request->user(),
                'brn_created' => $brn,
            ]);
            return response()->json(['success' => $name]);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'ee '.$th->getMessage()]);
        }
    }

    public function createRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $role = Role::create(['name' => $request->name]);

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Role',
                'content' => $role->name,
                'note' => 'store',
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' create role',
            [
                'user' => $request->user(),
                'role_created' => $role,
            ]);
            return response()->json(['success' => $role]);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'ee '.$th->getMessage()]);
        }
    }

    public function updateRole(Request $request){
        try {
            $role = Role::findByName($request->oldName);
            $role->update(['name' => $request->newName]);
            return response()->json(['success' => $request->all()]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['error' => 'ee '.$th->getMessage()]);
        }
    }

    public function createPerm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $permission = Permission::create(['name' => $request->name]);

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'permission',
                'content' => $permission->name,
                'note' => 'store',
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' create permission',
            [
                'user' => $request->user(),
                'perm_created' => $permission,
            ]);
            return response()->json(['success' => $permission]);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'ee '.$th->getMessage()]);
        }
    }

    public function createDpm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'prefix' => 'required|max:255',
            'branch' => 'max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $validatedData = $validator->validated();
            $name = $validatedData['name'];
            $prefix = $validatedData['prefix'];
            $branch = $validatedData['branch'];
            $dpm = new department;
            $brn = branch::find( $branch );
            $dpm->name = $name;
            $dpm->prefix = Str::upper($prefix);
            $dpm->branch = $branch;
            $dpm->agency = $brn->agency;
            $dpm->save();

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Department',
                'content' => $dpm->id,
                'note' => 'store',
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' create Department',
            [
                'user' => $request->user(),
                'dpm_created' => $dpm,
            ]);
            return response()->json(['success' => $name.' '.$branch]);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'ee '.$th->getMessage()]);
        }
    }

    public function deleteData(Request $request) {
        try {
            if ($request->type == 'agn') {
                agency::find( $request->delid )->delete();
            } elseif ($request->type == 'brn') {
                branch::find( $request->delid )->delete();
            } elseif ($request->type == 'dpm') {
                department::find( $request->delid )->delete();
            } elseif ($request->type == 'role') {
                Role::findByName($request->delid)->delete();
            } elseif ($request->type == 'perm') {
                Permission::findByName($request->delid)->delete();
            };

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'delete Data',
                'content' => $request->type. ' : '. $request->delid,
                'note' => 'delete',
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' deleted data',
            [
                'user' => $request->user(),
                'delete_type' => $request->type,
                'delete_id' => $request->delid,
            ]);
            return response()->json(['success' => $request->all() ]);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'ee '.$th->getMessage()]);
        }
    }

    public function restore($resType , $resId)
    {
        try {
            if ($resType == 'course') {
                $course = course::withTrashed()->find($resId);
                $course->restore();
            } elseif ($resType == 'quiz') {
                $quiz = quiz::withTrashed()->find($resId);
                $quiz->restore();
            }
            return response()->json(['success' => $resType ]);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'ee '.$th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            if ($request->editType == 'agn') {
                $agn = agency::find( $request->agnid );
                $agn->name = $request->name;
                $agn->address = $request->address;
                $agn->contact = $request->contact;
                $agn->save();
            } elseif ($request->editType == 'brn') {
                $brn = branch::find( $request->brnid );
                $brn->name = $request->name;
                $brn->agency = $request->agency;
                $brn->save();
            } elseif ($request->editType == 'dpm') {
                $dpm = department::find( $request->dpmid );
                $brn = branch::find( $request->branch );
                $dpm->name = $request->name;
                $dpm->branch = $request->branch;
                $dpm->prefix = Str::upper($request->prefix);
                $dpm->agency = $brn->agency;
                $dpm->save();
            }

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Data',
                'content' => $request->editType. ' : '. $request->name,
                'note' => 'Update',
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' updated data',
            [
                'user' => $request->user(),
                'update_type' => $request->editType,
                'data' => $request,
            ]);
            return response()->json(['success' => $request->all() ]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
