<?php

namespace App\Http\Controllers;

use App\Models\branch;
use App\Models\course;
use App\Models\department;
use App\Models\quiz;
use App\Models\Test;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class ReportController extends Controller
{
    public function courseReport(Request $request)
    {
        if ($request->user()->hasAnyPermission(['dCourse']) || $request->user()->hasAnyRole(['admin', 'superAdmin'])) {
            return view('report.course-report');
        } else {
            return redirect('/');
        }
    }

    public function testReport(Request $request)
    {
        if ($request->user()->hasAnyPermission(['dQuiz']) || $request->user()->hasAnyRole(['admin', 'superAdmin'])) {
            return view('report.test-report');
        } else {
            return redirect('/');
        }
    }
    public function summaryReport(Request $request)
    {
        if ($request->user()->hasAnyPermission(['dQuiz']) || $request->user()->hasAnyRole(['admin', 'superAdmin'])) {
            return view('report.summary-report');
        } else {
            return redirect('/');
        }
    }

    public function learningReport(Request $request)
    {
        return view('report.learning-report');
    }

    public function courseExport(Request $request) {
        $search = $request->search;
        if ($search == '' || is_null($search)) {
            if (auth()->user()->role == 'superAdmin') {
                $courses = course::orderBy('created_at', 'desc')->get();
            } else {
                $courses = course::where('agn', auth()->user()->agency)->orderBy('created_at', 'desc')->get();
            }
        } else {
            $query = course::where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%");
            });

            if (auth()->user()->role == 'superAdmin') {
                $courses = $query->orderBy('created_at', 'desc')->get();
            } else {
                $courses = $query->where('agn', auth()->user()->agency)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }
        return view('page.exports.course', compact('courses'));
    }

    public function testExport(Request $request) {
        $search_data = json_decode($request->searchData ?? "", true);
        $filter_tests = Test::orderBy('created_at', 'desc');
        $fuser = null;
        $fquiz = null;
        $fsdate = null;
        $fedate = null;
        if ($search_data) {
            // check if formdata has key filter_user
            if (array_key_exists('filter_user', $search_data)) {
                if ($search_data['filter_user'] != null) {
                    $filter_tests = $filter_tests->where('tester', $search_data['filter_user']);
                    $fuser = User::find($search_data['filter_user']);
                }
            }
            // check if formdata has key filter_course
            if (array_key_exists('filter_quiz', $search_data)) {
                if ($search_data['filter_quiz'] != null) {
                    $filter_tests = $filter_tests->where('quiz', $search_data['filter_quiz']);
                    $fquiz = quiz::find($search_data['filter_quiz']);
                }
            }
            if (array_key_exists('filter_sdate', $search_data)) {
                if ($search_data['filter_sdate'] != null) {
                    $filter_tests = $filter_tests->where('created_at', '>=', $search_data['filter_sdate']);
                    $fsdate = Carbon::parse($search_data['filter_sdate'])->format('d/m/Y');
                }
            }
            if (array_key_exists('filter_edate', $search_data)) {
                if ($search_data['filter_edate'] != null) {
                    $filter_tests = $filter_tests->where('created_at', '<=', $search_data['filter_edate']);
                    $fedate = Carbon::parse($search_data['filter_edate'])->format('d/m/Y');
                }
            }
        }
        $tests = $filter_tests->get();
        return view('page.exports.test', compact('tests', 'fuser', 'fquiz', 'fsdate', 'fedate'));
    }

    public function summaryExport(Request $request) {
        $search_data = json_decode($request->searchData ?? "", true);
        $filter_tests = Test::select(
            'tester',
            'quiz',
            'course_id',
            DB::raw('COUNT(*) as times_tested'),
            DB::raw('MAX(score) as best_score'),
            DB::raw('ROUND(AVG(score), 2) as average_score'),
            DB::raw('MAX(created_at) as latest_at')
        )->groupBy(['quiz', 'course_id', 'tester']);

        if (Auth::user()->role !== 'superAdmin') {
            $filter_tests->where('agn', auth()->user()->agency);
        }
        $fuser = null;
        $fquiz = null;
        $fsdate = null;
        $fedate = null;
        $fbrn = null;

        if ($search_data) {
            // check if formdata has key filter_user
            if (array_key_exists('filter_user', $search_data)) {
                if ($search_data['filter_user'] != null) {
                    $filter_tests->where('tester', $search_data['filter_user']);
                    $fuser = User::find($search_data['filter_user']);
                }
            }

            if (array_key_exists('filter_brn', $search_data) && $search_data['filter_brn'] != null) {
                $user_list = User::where('brn', $search_data['filter_brn'])->pluck('id')->toArray() ?? [];
                $filter_tests->whereIn('tester', $user_list);
                $fbrn = branch::find($search_data['filter_brn']);
            }

            // check if formdata has key filter_course
            if (array_key_exists('filter_quiz', $search_data)) {
                if ($search_data['filter_quiz'] != null) {
                    $filter_tests->where('quiz', $search_data['filter_quiz']);
                    $fquiz = quiz::find($search_data['filter_quiz']);
                }
            }
            if (array_key_exists('filter_sdate', $search_data)) {
                if ($search_data['filter_sdate'] != null) {
                    $filter_tests->where('created_at', '>=', $search_data['filter_sdate'] . ' 00:00:00')
                    ->where('created_at', '<=', $search_data['filter_sdate'] . ' 23:59:59');
                    $fsdate = Carbon::parse($search_data['filter_sdate'])->format('d/m/Y');
                }
            }
            // if (array_key_exists('filter_edate', $search_data)) {
            //     if ($search_data['filter_edate'] != null) {
            //         $filter_tests = $filter_tests->where('created_at', '<=', $search_data['filter_edate']);
            //         $fedate = Carbon::parse($search_data['filter_edate'])->format('d/m/Y');
            //     }
            // }
        }
        $tests = $filter_tests->orderBy(DB::raw('ROUND(AVG(score), 2)'), 'desc')->get();
        return view('page.exports.summary', compact('tests', 'fuser', 'fquiz', 'fsdate', 'fedate', 'fbrn'));
    }
}
