<?php

namespace App\Http\Controllers;

use App\Models\course;
use App\Models\User;
use App\Models\user_request;
use Illuminate\Http\Request;

class ReqController extends Controller
{
    public function addReq () {
        $users = User::where('agency', auth()->user()->agency)->get();
        $courses = course::where('agn', auth()->user()->agency)->get();
        return view('page.add_request', compact('users', 'courses'));
    }

    public function storeReq (Request $request) {

        try {
            $alert2staff = User::role('staff')->where('agency', auth()->user()->agency)->pluck('id')->toArray();
            // Use array_map to wrap each value in double quotes
            $encodedAlert = json_encode(array_map(function ($value) {
                return (string)$value;
            }, $alert2staff));
            $req = user_request::create([
                'user' => $request->user()->id,
                'type' => $request->type,
                'target' => $request->target,
                'status' => 0,
                'alert' => $encodedAlert,
                'content' => '',
                'agn' => auth()->user()->agency

            ]);

            if ($request->type === 'course') {
                $req->content = json_encode($request->course ?? []);
            } else {
                $req->content = $request->message ?? '';
            }

            $req->save();

            return redirect()->route('request.all')->with('success','success');
        } catch (\Throwable $th) {
            return redirect()->route('request.all')->with('error', $th->getMessage());
        }
    }

    public function markAsFinish($id)
    {
        try {
            $notification = user_request::find($id);

            $alert = [];
            $alert[] = $notification->user;
            $alert[] = $notification->target;

            $notification->alert = json_encode($alert);
            $notification->status = 1;
            $notification->finish_by = auth()->user()->id;
            $notification->save();

            return response()->json(['status' => 'success', 'response' => json_encode($alert)], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'error' => $th->getMessage()], 404);
        }
    }

    public function markAsFail($id)
    {
        try {
            $notification = user_request::find($id);

            $alert = $notification->alert;
            $alert[] = $notification->user;

            if ($notification->target !== '-') {
                $alert[] = $notification->target;
            }

            $notification->alert = json_encode($alert);
            $notification->status = 2;
            $notification->finish_by = auth()->user()->id;
            $notification->save();

            return response()->json(['status' => 'success', 'response' => json_encode($alert)], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'error' => $th->getMessage()], 404);
        }
    }

    public function markAsAccept()
    {
        try {
            $alert_list = user_request::where('alert', 'LIKE', '%"' . auth()->user()->id . '"%')->where('status', '1')->get();

            foreach ( $alert_list as $key =>  $alert_each) {
                $alert = $alert_each->alert;
                $filteredData = array_filter($alert, function ($value) {
                    return $value != auth()->user()->id;
                });
                $result = array_values($filteredData);
                $alert_each->alert = json_encode($result);
                $alert_each->save();
            }

            return response()->json(['status' => 'success', 'success' => "success"], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'error' => $th->getMessage()], 404);
        }
    }
}
