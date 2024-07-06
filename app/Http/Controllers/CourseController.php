<?php

namespace App\Http\Controllers;

use App\Models\quiz;
use Illuminate\Http\Request;
use App\Models\course;
use App\Models\lesson;
use App\Models\course_group;
use App\Models\department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Activitylog;
use App\Models\course_has_group;
use App\Models\course_has_quiz;
use App\Models\user_has_course;

use function PHPUnit\Framework\isEmpty;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

    }

    public function addGroup(Request $request) {
        try {
            $group = course_group::create([
                'name' => $request->gname,
                'by' => auth()->id(),
                'agn' => $request->user()->agency
            ]);

            $selectedCourses = $request->selected_course;
            foreach ($selectedCourses as $key => $selectedCourse) {
                $gcourse = course_has_group::where('group_id', $group->id)->where('course_id', $selectedCourse)->get();
                if (count($gcourse) == 0) {
                    course_has_group::create([
                        'group_id' => $group->id,
                        'course_id' => $selectedCourse,
                    ]);
                }
            }
            course_has_group::where('group_id', $group->id)->whereNotIn('course_id', $selectedCourses)->delete();

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'addGroup',
                'content' => $request->gname,
                'note' => $group->id,
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' search dpm',
            [
                'user_id' => auth()->id(),
                'content' => 'addGroup '. json_encode($group),
            ]);
            return redirect()->back()->with('success','Group has been added.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error','Something wrong!');
        }
    }

    public function delGroup($gid) {
        try {
            course_group::find($gid)->delete();
            return response()->json(['success' => $gid]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function searchEn(Request $request)
    {
        $search = $request->get('search');
        $course_list = user_has_course::where('user_id', $request->user()->id)->get(['course_id']);

        if ($search !== '!all!') {
            // search in title
            $courses1 = course::whereIn("id", $course_list)
            // Add your search condition here before paginate
            ->when($search, function ($query) use ($search) {
                return $query->where('title', 'like', '%'.$search.'%');
            });

            // search in course code
            $courses2 = course::whereIn("id", $course_list)
                    // Add your search condition here before paginate
                    ->when($search, function ($query) use ($search) {
                        return $query->where('code', 'like', '%'.$search.'%');
                    })->union($courses1);

            // query
            $courses = $courses2->paginate(12);
        } else {
            $courses = course::whereIn("id", $course_list)->paginate(12);
        }

        Activitylog::create([
            'user' => auth()->id(),
            'module' => 'search',
            'content' => $search,
            'note' => 'dpm',
            'agn' => $request->user()->agency
        ]);
        Log::channel('activity')->info('User '. $request->user()->name .' search dpm',
            [
                'user_id' => auth()->id(),
                'content' => $search,
            ]);
        return view('partials.courses', compact('courses'));
    }

    public function searchDpm(Request $request)
    {
        $search = $request->get('search');

        if ($search !== '!all!') {
            // search in title
            $courses1 = course::where('permission->dpm', "true")->where('agn', $request->user()->agency)
            ->where('dpm', $request->user()->dpm)
            // Add your search condition here before paginate
            ->when($search, function ($query) use ($search) {
                return $query->where('title', 'like', '%'.$search.'%');
            });

            // search in course code
            $courses2 = course::where('permission->dpm', "true")->where('agn', $request->user()->agency)
                    ->where('dpm', $request->user()->dpm)
                    // Add your search condition here before paginate
                    ->when($search, function ($query) use ($search) {
                        return $query->where('code', 'like', '%'.$search.'%');
                    })->union($courses1);

            // query
            $courses = $courses2->paginate(12);
        } else {
            if ($request->user()->hasAnyRole('admin', 'staff')) {
                $courses = course::where('permission->dpm', "true")->where('agn', $request->user()->agency)->paginate(12);
            } else {
                $courses = course::where('permission->dpm', "true")->Where('dpm', $request->user()->dpm)->paginate(12);
            }
        }

        Activitylog::create([
            'user' => auth()->id(),
            'module' => 'search',
            'content' => $search,
            'note' => 'dpm',
            'agn' => auth()->user()->agency
        ]);
        Log::channel('activity')->info('User '. $request->user()->name .' search dpm',
            [
                'user_id' => auth()->id(),
                'content' => $search,
            ]);
        return view('partials.courses', compact('courses'));
    }

    public function searchAll(Request $request)
    {
        $search = $request->get('search');

        if ($search !== '!all!') {
            // search in title
            $courses = course::where('permission->all', "true")->where('agn', $request->user()->agency)
                ->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
                })->paginate(12);
        } else {
            $courses = course::where('permission->all', "true")->where('agn', $request->user()->agency)->paginate(12);
        }


        Activitylog::create([
            'user' => auth()->id(),
            'module' => 'search',
            'content' => $search,
            'note' => 'all course',
            'agn' => auth()->user()->agency
        ]);
        Log::channel('activity')->info('User '. $request->user()->name .' search all course',
        [
            'user_id' => auth()->id(),
            'content' => $search,
        ]);
        return view('partials.courses', compact('courses'));
    }


    public function store(Request $request) {
        $request->validate([
            'title' => ['required', 'string', 'max:5000'],
        ]);
        if (!is_null($request->desc)) {
            $request->validate([
                'desc' => ['string', 'max:20000'],
            ]);
        }
        if ($request->hasFile('cimg')) {
            $request->validate([
                'cimg' => ['file','mimes:jpeg,png,jpg','max:10240'], // 10MB max size, adjust as needed
            ]);
        }

        try {
            if ($request->hasFile('cimg')) {
                $file = $request->file('cimg');
                $filename = time(). '_' . $file->getClientOriginalName(); // Unique name

                // Define the path within the public directory where you want to store the files
                $destinationPath = public_path('uploads/course_imgs');

                // Check if the directory exists, if not, create it
                if (!File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }

                // Move the file to the public directory
                $file->move($destinationPath, $filename);
            }


            $dpmName = department::find($request->user()->dpm);
            $courses = course::withTrashed()->where('dpm', $request->user()->dpm)->count();
            $courseNum = sprintf('%03d', $courses);
            $course_perm = [
                'all'=> $request->allPerm ?? '',
                'dpm'=> $request->dpmPerm ?? '',
            ];
            $course = course::create([
                'title'=> $request->title,
                'description' => $request->desc,
                'permission' => json_encode($course_perm),
                'teacher' => $request->user()->id,
                'dpm' => $request->user()->dpm,
                'code' => ($dpmName->prefix ?? 'ID').($courseNum),
                'img' => $filename ?? null,
                'agn' => $request->user()->agency
            ]);

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Course',
                'content' => $course->id,
                'note' => 'store',
                'agn' => $request->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' store course',
            [
                'user_id' => auth()->id(),
                'course_id' => $course->id,
            ]);
            return response()->json(['success' => $request->all()]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function update(Request $request) {
        $request->validate([
            'courseId' => ['required', 'max:255'],
            'title' => ['required', 'string', 'max:5000'],
        ]);
        if (!is_null($request->desc)) {
            $request->validate([
                'desc' => ['string', 'max:20000'],
            ]);
        }
        if ($request->hasFile('cimg')) {
            $request->validate([
                'cimg' => ['file','mimes:jpeg,png,jpg','max:10240'], // 10MB max size, adjust as needed
            ]);
        }
        try {
            if ($request->hasFile('cimg')) {
                $file = $request->file('cimg');
                $filename = time(). '_' . $file->getClientOriginalName(); // Unique name

                // Define the path within the public directory where you want to store the files
                $destinationPath = public_path('uploads/course_imgs');

                // Check if the directory exists, if not, create it
                if (!File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }

                // Move the file to the public directory
                $file->move($destinationPath, $filename);
            }

            $course_perm = [
                'all'=> $request->allPerm ?? false,
                'dpm'=> $request->dpmPerm ?? false,
            ];
            $courses = course::find( $request->courseId);
            $updateData = [
                'permission' => json_encode($course_perm),
                'title' => $request->title,
                'description' => $request->desc,
                'dpm' => $request->user()->dpm,
            ];

            if ($request->hasFile('cimg')) {
                $updateData['img'] = $filename;
                $filePath = public_path('uploads/course_imgs/' . $courses->img);
                // Check if the file exists before attempting to delete
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            $courses->update($updateData);

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Course',
                'content' => $courses->id,
                'note' => 'update',
                'agn' => $request->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' update course',
            [
                'user_id' => auth()->id(),
                'course_id' => $courses->id,
            ]);
            return response()->json(['success' => $request->all()]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function delete(Request $request) {
        try {
            $status = '';
            if ($request->deltype == 'course') {
                $courses = course::find($request->delid);
                $filePath = public_path('uploads/course_imgs/' . $courses->img);
                // Check if the file exists before attempting to delete
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
                $courses->delete();
            } else if ($request->deltype == 'lesson') {
                lesson::find($request->delid)->delete();
            }

            Activitylog::create([
                'user' => auth()->id(),
                'module' => $request->deltype,
                'content' => $request->delid,
                'note' => 'delete',
                'agn' => $request->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' delete course or lesson',
            [
                'user_id' => auth()->id(),
                'delete_type' => $request->deltype,
                'delete_id' => $request->delid
            ]);
            return response()->json(['success' => $request->all()]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function enroll(Request $request, $cid) {
        try {
            if (!user_has_course::where('user_id', $request->user()->id)->where('course_id', $cid)->exists()) {
                user_has_course::create([
                    'user_id' => $request->user()->id,
                    'course_id' => $cid
                ]);
            }

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Course',
                'content' => $cid,
                'note' => 'enroll',
                'agn' => $request->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' enroll course',
            [
                'user_id' => $request->user()->id,
                'course_id' => $cid,
            ]);
            return redirect()->route('course.detail', ['id' => $cid]);
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->route('course.detail', ['id' => $cid]);
        }

    }

    public function search(Request $request)
    {
        // Retrieve the search keyword and department filters from the request
        $search = $request->input('search');
        $departmentIds = $request->input('departments');

        // Start building the query
        $query = Course::where('permission->all', true)->where('agn', $request->user()->agency);

        // If a search keyword was provided, use it to filter the courses
        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%") // assuming the courses table has 'name' and 'description' columns
                  ->orWhere('code', 'LIKE', "%{$search}%");
        }

        // If department filters were provided, use them to filter the courses
        if (!empty($departmentIds)) {
            $query->whereIn('dpm', $departmentIds);
        }

        // Execute the query and get the results
        $courses = $query->get();

        // Load departments for the filters
        $dpms = department::all();
        // Return the search view with the results and departments

        Log::channel('activity')->info('User '. $request->user()->name .' search all course',
        [
            'user_id' => auth()->id(),
            'content' => $search,
        ]);
        return view('page.courses.allcourse', compact('courses', 'dpms', 'departmentIds', 'search'));
    }

    public function searchMy(Request $request)
    {
        $course_list = user_has_course::where('user_id', $request->user()->id)->get(['course_id']);
        // Retrieve the search keyword and department filters from the request
        $search = $request->input('search');
        if (!($request->input('departments'))) {
            $departmentIds = [];
        } else {
            $departmentIds = $request->input('departments');
        }
        $userId = $request->user()->id; // Get the current user's ID

        // Start building the query with the initial condition for the current user
        if ($request->user()->hasAnyRole(['admin', 'staff'])) {
            $query = Course::where('permission->dpm', true)->where('agn', $request->user()->agency);
        } else {
            $query = Course::where('permission->dpm', true)->where('agn', $request->user()->agency)
                        ->where(function ($query) use ($request, $course_list) {
                            $query->whereIn("id", $course_list)
                                ->orWhere('dpm', $request->user()->dpm);
                        });
        }

        // Nest the additional search and department conditions
        if ($search || !empty($departmentIds)) {
            $query->where(function ($subquery) use ($search, $departmentIds) {
                // If a search keyword was provided, use it to filter the courses
                if ($search) {
                    $subquery->where('title', 'LIKE', "%{$search}%")
                            ->orWhere('description', 'LIKE', "%{$search}%")
                            ->orWhere('code', 'LIKE', "%{$search}%");
                }

                // If department filters were provided, use them to filter the courses
                if (!empty($departmentIds)) {
                    $subquery->whereIn('dpm', $departmentIds);
                }
            });
        }

        // Execute the query and get the results
        $courses = $query->get();

        // Load departments for the filters
        $dpms = Department::all();

        Log::channel('activity')->info('User '. $request->user()->name .' search my course',
        [
            'user_id' => auth()->id(),
            'content' => $search,
        ]);
        // Return the search view with the results and departments
        return view('page.courses.myclassroom', compact('courses', 'dpms', 'departmentIds', 'search'));
    }


    public function addLesson(Request $request): RedirectResponse {
        $request->validate([
            'topic' => ['required', 'string', 'max:5000'],
            'courseid' => ['required', 'string', 'max:255'],
        ]);
        try {
            $lesson = lesson::create([
                'topic'=> $request->topic,
                'desc'=> $request->desc ?? '',
                'course'=> $request->courseid,
                'agn' => $request->user()->agency
            ]);

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'lesson',
                'content' => $lesson->id,
                'note' => 'store',
                'agn' => $request->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' add lesson',
            [
                'user_id' => auth()->id(),
                'lesson_id' => $lesson->id,
            ]);
            alert()->success('Success','Lesson has been saved!');
            return back();
        } catch (\Throwable $th) {
            alert()->error('Some thing worng!', $th->getMessage());
            return back();
        }
    }

    public function updateLesson(Request $request): RedirectResponse {
        $request->validate([
            'lessid' => ['string', 'max:255'],
            'topic' => ['required', 'string', 'max:5000'],
        ]);

        if ($request->desc) {
            $request->validate([
                'desc' => ['string', 'max:10000'],
            ]);
        }

        try {
            $lesson = Lesson::find($request->lessid);
            $lesson->update([
                'topic' => $request->topic,
                'desc' => $request->desc ?? ''
            ]);

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'lesson',
                'content' => $lesson->id,
                'note' => 'update',
                'agn' => $request->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' update lesson',
            [
                'user_id' => auth()->id(),
                'content' => $lesson,
            ]);
            alert()->success('Success','Lesson has been saved!');
            return back();
        } catch (\Throwable $th) {
            alert()->error('Some thing worng!', $th->getMessage());
            return back();
        }
    }

    public function subLessAdd(Request $request) {
        try {
            if ($request->addType == "file") {
                $request->validate([
                    'label' => 'required|string|max:255',
                    'content' => 'required|file|mimes:jpeg,png,pdf,svg,doc,docx,xls,xlsx,ppt,pptx,txt,mp4|max:262144', // 256MB max size, adjust as needed
                    'lessId' => 'required',
                    'addType' => 'required',
                ]);

                if ($request->hasFile('content')) {
                    $file = $request->file('content');
                    $filename = time(). '_' . $file->getClientOriginalName(); // Unique name

                    // Define the path within the public directory where you want to store the files
                    $destinationPath = public_path('uploads/sublessons');

                    // Check if the directory exists, if not, create it
                    if (!File::isDirectory($destinationPath)) {
                        File::makeDirectory($destinationPath, 0755, true);
                    }

                    // Move the file to the public directory
                    $file->move($destinationPath, $filename);

                    // Generate the URL to the file
                    $url = asset('uploads/sublessons/' . $filename);
                }

                $subless = [
                    'id'=> date('dmYHi'),
                    'type'=> $request->addType,
                    'label'=> $request->label,
                    'content'=> $filename,
                    'date'=> date('Y-m-d'),
                ];
                $lesson = lesson::find($request->lessId);
                if (is_null($lesson->sub_lessons)) {
                    $subContainer = [];
                } else {
                    $subContainer = json_decode($lesson->sub_lessons);
                }
                $subContainer[] = $subless;
                if ($lesson) {
                    $lesson->sub_lessons = $subContainer;
                    $lesson->save();
                }
            } else {
                $request->validate([
                    'content' => ['required','string', 'max:10000'],
                    'label' => ['required', 'string', 'max:500'],
                    'lessId' => 'required',
                    'addType' => 'required',
                    'numQuest' => ['integer', 'max:500']
                ]);
                $subless = [
                    'id'=> date('dmYHi'),
                    'type'=> $request->addType,
                    'label'=> $request->label,
                    'content'=> $request->content,
                    'date'=> date('Y-m-d'),
                    'num_quest' => $request->numQuest,
                ];
                $lesson = lesson::find($request->lessId);
                if (is_null($lesson->sub_lessons)) {
                    $subContainer = [];
                } else {
                    $subContainer = json_decode($lesson->sub_lessons);
                }
                $subContainer[] = $subless;
                if ($lesson) {
                    $lesson->sub_lessons = $subContainer;
                    $lesson->save();
                }

                if ($request->addType == "quiz") {
                    $quiz = quiz::find($request->content);
                    if (!course_has_quiz::where('course_id', $lesson->getCourse->id)->where('quiz_id', $quiz->id)->exists()) {
                        course_has_quiz::create([
                            'course_id' => $lesson->getCourse->id,
                            'quiz_id' => $quiz->id
                        ]);
                    }
                }
            }

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'sublesson',
                'content' => $lesson->id,
                'note' => 'add sublesson',
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' add sub lesson',
            [
                'user_id' => auth()->id(),
                'content' => $lesson,
            ]);
            return response()->json(['success' => $request->all()]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['error' => $th->getMessage()]);
        }
    }


    public function subLessDel(Request $request) {
        try {
            $lesson = lesson::find($request->lessId);
            $subContainer = [];

            if ($lesson) {
                if (!is_null($lesson->sub_lessons)) {
                    $oldContainer = json_decode($lesson->sub_lessons);
                    foreach ($oldContainer as $key => $item) {
                        if ($key != $request->delid) {
                            $subContainer[] = $item;
                        } else {
                            if ($item->type == 'file') {
                                $filePath = public_path('uploads/sublessons/' . $item->content);
                                // Check if the file exists before attempting to delete
                                if (File::exists($filePath)) {
                                    File::delete($filePath);
                                }
                            } elseif ($item->type == "quiz") {
                                $quiz = quiz::find($item->content);
                                course_has_quiz::where('course_id', $lesson->getCourse->id)->where('quiz_id', $quiz->id)->delete();
                            }
                        }
                    }
                }
            }

            $lesson->sub_lessons = $subContainer;
            $lesson->save();

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'sublesson',
                'content' => $lesson->id,
                'note' => 'remove',
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' delete sub lesson',
            [
                'user_id' => auth()->id(),
                'content' => $lesson,
                'added' => $subContainer,
            ]);
            return response()->json(['success' => $subContainer]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

}
