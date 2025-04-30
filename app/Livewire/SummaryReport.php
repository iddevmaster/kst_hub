<?php

namespace App\Livewire;

use Date;
use Livewire\Component;
use Livewire\WithPagination;

use App\Models\branch;
use App\Models\quiz;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SummaryReport extends Component
{
    use WithPagination;

    protected $testsQuery;
    public $formData = [
        'filter_date' => null,
        'filter_brn' => null,
        'filter_type' => null,
    ];
    public $filterData = [
        'date' => null,
        'brn' => null,
        'type' => null,
        'users' => null,
    ];
    public $course_types = [
        '100' => 'รถยนต์',
        '200' => 'รถจักรยานยนต์',
        '300' => 'รถขนส่ง ท.2(มีใบขับขี่รถยนต์)',
        '400' => 'รถขนส่ง ท.2(ไม่มีใบขับขี่รถยนต์)',
        '500' => 'รถอื่นๆ',
        '700' => 'รถขนส่ง ท.3',
    ];
    public $branches = [
        'idmskk' => 'โนนทัน',
        'idmsLLK' => 'ลำลูกกา',
        'idmsMK' => 'มหาสาคาม',
        'idmsPRO' => 'เดอะโปรเฟชชั่นแนล',
        'idmsPY' => 'พยัคฆภูมิพิสัย',
        'idmsTK' => 'แก่งคอย',
    ];
    public $is_loading = false;
    public $user_ids= [];

    public function mount()
    {
        $this->formData['filter_date'] = now()->format('Y-m-d');
    }

    public function searchTest()
    {
        $this->filterData['date'] = $this->formData['filter_date'];
        $this->filterData['brn'] = $this->branches[$this->formData['filter_brn']];
        $this->filterData['type'] = $this->course_types[$this->formData['filter_type']];
        $apiUrl = 'http://www.dsmsys.net/'. $this->formData['filter_brn'] . '/api/examlist/?date=' . $this->formData['filter_date'] . '&course_type_id=' . $this->formData['filter_type'];
        $this->is_loading = true;
        try {
            $response = Http::withHeaders([
                "Authorization" => "Basic YWRtaW50ejpRYkh2NGJxZA=="
            ])->get($apiUrl);

            if ($response->successful()) {
                $testerList = $response->json();
            } else {
                session()->flash('error', 'Failed to fetch data from the API.');
            }

            if ($testerList && count($testerList) > 0) {
                $ids = collect($testerList)->pluck('std_id')->all();
                $this->user_ids = User::whereIn('username', $ids)->pluck('id')->all();
                $this->filterData['users'] = $this->user_ids;
                $this->testsQuery = Test::select(
                    'tester',
                    'quiz',
                    'course_id',
                    DB::raw('COUNT(*) as times_tested'),
                    DB::raw('MAX(score) as best_score'),
                    DB::raw('ROUND(AVG(score), 2) as average_score')
                    )
                    ->whereIn('tester', $this->user_ids)
                    ->groupBy(['quiz', 'course_id', 'tester'])->orderBy(DB::raw('ROUND(AVG(score), 2)'), 'desc')->get();
            }
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        } finally {
            $this->is_loading = false;
        }
    }

    public function render()
    {

        return view('livewire.summary-report', [
            'tests' => $this->testsQuery,
        ]);
    }
}
