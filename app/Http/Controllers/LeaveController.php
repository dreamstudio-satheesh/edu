<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\Leave;
use App\Models\Staff;
use App\Models\Holiday;
use App\Models\Teacher;
use App\Models\LeaveDetail;
use App\Models\LeaveMaster;
use App\Models\SessionYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Auth::user()->can('leave-create')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }

        $sessionYears = SessionYear::all();
        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        $currentSessionYear = SessionYear::where('id',$currentSessionYearId)->first();

        $leaveMaster = LeaveMaster::where('session_year_id', $currentSessionYearId)->first();
        $holiday_days = '';

        if ($leaveMaster) {
            $holiday_days = $leaveMaster->holiday_days;
        }

        $teachers = Teacher::with('user')->get();
        $staff = Staff::with('user')->get();

        $users = $teachers->merge($staff);

        $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',5 => 'May',6 => 'June',7 => 'July',8 => 'August',9 => 'September',10 => 'October',11 => 'November',12 => 'December'
        ];

        $holiday = Holiday::whereDate('date', '>=', $currentSessionYear->start_date)->whereDate('date', '<=', $currentSessionYear->end_date)->get()->pluck('date')->toArray();

        $public_holiday = implode(',', $holiday);

        return view('leave.index',compact('sessionYears','currentSessionYearId','currentSessionYear','holiday_days', 'users', 'months', 'public_holiday', 'leaveMaster'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('leave-create')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'reason'  => 'required',
            'from_date' => 'required',
            'to_date' => 'required|after_or_equal:from_date',
            'leave_master_id' => 'required',

            'type.*' => 'required|array',
            'files.*' => 'nullable',
        ],[
            'type' => 'required'
        ],
        [
            'leave_master_id.required' => 'Kindly contact the school admin to update settings for continued access.',
            'type.required' => 'Kindly select different dates as the ones mentioned are already allocated as holidays.'
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {

            if($request->type)
            {
                $data = [
                    'user_id' => Auth::user()->id,
                    'reason' => $request->reason,
                    'from_date' => date('Y-m-d', strtotime($request->from_date)),
                    'to_date' => date('Y-m-d', strtotime($request->to_date)),
                    'leave_master_id' => $request->leave_master_id,
                    'status' => "0"
                ];

                $leave = Leave::create($data);

                $leaveDetail = array();


                foreach ($request->type as $key => $type)
                {
                    $leaveDetail = new LeaveDetail();
                    $leaveDetail->leave_id = $leave->id;
                    $leaveDetail->date = date('Y-m-d', strtotime($key));
                    $leaveDetail->type = $type[0];
                    $leaveDetail->save();

                }



                if ($request->hasFile('files')) {
                    foreach ($request->file('files') as $file_upload) {

                        $file = new File();
                        $file->modal_type = "App\Models\Leave";
                        $file->modal_id = $leave->id;
                        $file->file_name = $file_upload->getClientOriginalName();
                        $file->type = 1;
                        $file->file_url = $file_upload->store('leave', 'public');
                        $file->save();

                    }

                }

                $response = array(
                    'error' => false,
                    'message' => trans('data_store_successfully')
                );
            }
            else {
                $response = array(
                    'error' => true,
                    'message' => trans('The dates you have chosen are already allocated as holidays select different dates.')
                );
            }

        } catch (\Throwable $e) {
            DB::rollback();
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'exception' => $e
            );
        }
        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        // dd($request->all());
        if (!Auth::user()->can('leave-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }

        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];


        $search = $request->search;
        $session_year_id = $request->session_year_id;
        $filter_upcoming = $request->filter_upcoming;
        $month_id = $request->month_id;


        $sql = Leave::with('leave_detail','file')->where('user_id',Auth::user()->id)
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%");
                    });
                });
            });

        if ($session_year_id) {
            $sql->whereHas('leave_master', function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id);
            });
        }

        $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
            $q->where('type', 'Full');
        }]);

        $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
            $q->whereNot('type', 'Full');
        }]);

        if ($filter_upcoming) {
            if ($filter_upcoming == 'Today') {
                $sql->whereDate('from_date', '<=', Carbon::now()->format('Y-m-d'))->whereDate('to_date', '>=', Carbon::now()->format('Y-m-d'));
            }
            if ($filter_upcoming == 'Tomorrow') {
                $tomorrow_date = Carbon::now()->addDay()->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($tomorrow_date) {
                    $q->whereDate('date', '<=', $tomorrow_date)->whereDate('date', '>=', $tomorrow_date);
                });
            }
            if ($filter_upcoming == 'Upcoming') {
                $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($upcoming_date) {
                    $q->whereDate('date', '>', $upcoming_date);
                });
            }
        }

        if ($month_id) {
            $sql->whereHas('leave_detail', function ($q) use ($month_id) {
                $q->whereMonth('date', $month_id);
            });
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;


        foreach ($res as $row) {

            $operate = '';
            $operate = '<a href='.route('leave-status.update',$row->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';

            if ($row->status == 0) {
                $operate .= '<a href='.route('leave.destroy',$row->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id=' . $row->id . '><i class="fa fa-trash"></i></a>';
            }
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['from_date'] = date('d-m-Y', strtotime($row->from_date));
            $tempRow['to_date'] =  date('d-m-Y', strtotime($row->to_date));
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['reason'] = $row->reason;
            $tempRow['status'] = $row->status;
            $tempRow['leave_detail'] = $row->leave_detail;
            $tempRow['file'] = $row['file'];
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        // dd($bulkData);
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('leave-delete')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);

        }

        try {
            $leave = Leave::find($id);

            if ($leave->file) {
                foreach ($leave->file as $file) {
                    if (Storage::disk('public')->exists($file->file_url)) {
                        Storage::disk('public')->delete($file->file_url);
                    }
                }
            }
            $leave->file()->delete();

            $leave->delete();
            $response = array(
                'error' => false,
                'message' => trans('data_delete_successfully'),
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e
            );
        }

        return response()->json($response);
    }

    public function leaveReportIndex()
    {
        if (!Auth::user()->can('leave-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }

        $users = null;

        if (Auth::user()->can('leave-approve')) {
            $teachers = Teacher::with('user')->get();
            $staff = Staff::with('user')->get();

            $users = $teachers->merge($staff);
        }


        $sessionYears = SessionYear::all();

        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        return view('leave.leave_details', compact('users','sessionYears','currentSessionYearId'));
    }

    public function leaveDetails(Request $request)
    {
        if (!Auth::user()->can('leave-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }

        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];

        $session_year_id = $request->session_year_id;

        $staff_id = $request->staff_id;
        if (!$staff_id) {
            $staff_id = Auth::user()->id;
        }

        $leaveMaster = LeaveMaster::with('session_year')->where('session_year_id', $session_year_id)->first();

        // Get months starting from session year
        $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',5 => 'May',6 => 'June',7 => 'July',8 => 'August',9 => 'September',10 => 'October',11 => 'November',12 => 'December'
        ];

        $bulkData = array();
        $bulkData['total'] = count($months);
        $rows = array();
        $no = 1;



        foreach ($months as $key => $month) {
            DB::enableQueryLog();
            $leaves = LeaveDetail::whereMonth('date', $key)
                ->whereHas('leave', function ($q) use ($session_year_id, $staff_id) {
                    $q->where('user_id', $staff_id)->where('status', 1)
                        ->whereHas('leave_master', function ($q) use ($session_year_id) {
                            $q->where('session_year_id', $session_year_id);
                        });
                })->get();


            $allocated = 0;
            $total_used_leaves = 0;

            if ($leaveMaster) {

                $tempRow['allocated'] = $leaveMaster->total_leave;
                $allocated = $leaveMaster->total_leave;
            }
            $tempRow['lwp'] = '-';
            $lwp = 0;
            $total_leaves = $leaves->count();
            // dd($total_leaves);
            $total_used_leaves = $total_leaves - ($leaves->where('type','!=', 'Full')->count() / 2);

            if ($allocated < $total_used_leaves) {
                $lwp = $total_used_leaves - $allocated;;
                $tempRow['lwp'] = $lwp;
                $tempRow['used_cl'] = $total_used_leaves - $lwp;
            } else {
                $tempRow['used_cl'] = '-';
                if ($total_used_leaves) {
                    $tempRow['used_cl'] = $total_used_leaves;
                }
            }
            $tempRow['total'] = '-';
            if ($total_used_leaves) {
                $tempRow['total'] = $total_used_leaves;
            }

            if ($total_used_leaves >= $allocated) {
                $tempRow['remaining_cl'] = '-';
                $tempRow['remaining_total'] = '-';
            } else {
                $tempRow['remaining_cl'] = $total_used_leaves != 0 ? $allocated - $total_used_leaves : '-';
                $tempRow['remaining_total'] = $total_used_leaves != 0 ? $allocated - $total_used_leaves : '-';
            }

            $tempRow['no'] = $no++;
            $tempRow['month'] = $month;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function leaveRequestIndex()
    {
        if (!Auth::user()->can('leave-approve')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }

        $sessionYears = SessionYear::all();
        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        $currentSessionYear = SessionYear::where('id',$currentSessionYearId)->first();

        $leaveMaster = LeaveMaster::where('session_year_id', $currentSessionYearId)->first();
        $holiday_days = '';

        if ($leaveMaster) {
            $holiday_days = $leaveMaster->holiday_days;
        }

        $teachers = Teacher::with('user')->get();
        $staff = Staff::with('user')->get();

        $users = $teachers->merge($staff);

        $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',5 => 'May',6 => 'June',7 => 'July',8 => 'August',9 => 'September',10 => 'October',11 => 'November',12 => 'December'
        ];

        $holiday = Holiday::whereDate('date', '>=', $currentSessionYear->start_date)->whereDate('date', '<=', $currentSessionYear->end_date)->get()->pluck('date')->toArray();

        $public_holiday = implode(',', $holiday);

        return view('leave.leave_request',compact('sessionYears','currentSessionYearId','currentSessionYear','holiday_days', 'users', 'months', 'public_holiday'));
    }

    public function leaveRequestShow(Request $request)
    {
        if (!Auth::user()->can('leave-approve')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }

        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];


        $search = $request->search;
        $session_year_id = $request->session_year_id;
        $filter_upcoming = $request->filter_upcoming;
        $month_id = $request->month_id;
        $user_id = $request->user_id;

        $sql = Leave::with('leave_detail','user', 'file')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%")->orwhereHas('user', function ($q) use ($search) {
                            $q->whereRaw('concat(first_name," ",last_name) like ?', "%$search%");
                        });
                    });
                });
            });

        if ($session_year_id) {
            $sql->whereHas('leave_master', function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id);
            });
        }

        if ($filter_upcoming != 'All') {
            if ($filter_upcoming == 'Today') {
                $sql->whereDate('from_date', '<=', Carbon::now()->format('Y-m-d'))->whereDate('to_date', '>=', Carbon::now()->format('Y-m-d'));
            }
            if ($filter_upcoming == 'Tomorrow') {
                $tomorrow_date = Carbon::now()->addDay()->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($tomorrow_date) {
                    $q->whereDate('date', '<=', $tomorrow_date)->whereDate('date', '>=', $tomorrow_date);
                });
            }
            if ($filter_upcoming == 'Upcoming') {
                $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($upcoming_date) {
                    $q->whereDate('date', '>', $upcoming_date);
                });
            }
        }

        if ($month_id) {
            $sql->whereHas('leave_detail', function ($q) use ($month_id) {
                $q->whereMonth('date', $month_id);
            });
        }

        if ($user_id) {
            $sql->where('user_id', $user_id);
        }

        $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
            $q->where('type', 'Full');
        }]);

        $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
            $q->whereNot('type', 'Full');
        }]);
        $total = $sql->count();

        $sql->orderBy('id', 'DESC')->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            $operate = '<a href='.route('leave-status.update',$row->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href='.route('leave.destroy',$row->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id=' . $row->id . '><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->user->first_name .' '. $row->user->last_name;
            $tempRow['from_date'] = date('d-m-Y', strtotime($row->from_date));
            $tempRow['to_date'] = date('d-m-Y', strtotime($row->to_date));
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['leave_detail'] = $row->leave_detail;
            $tempRow['file'] = $row->file;
            $tempRow['reason'] = $row->reason;
            $tempRow['status'] = $row->status;
            $tempRow['operate'] = $operate;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);

    }

    public function leaveStatusUpdate(Request $request)
    {
        if (!Auth::user()->can('leave-approve')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }

        try {

            $leave = Leave::findOrFail($request->id);
            $leave->status = $request->status;
            $leave->save();

            $response = array(
                'error' => false,
                'message' => trans('data_update_successfully')
            );

        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'exception' => $e
            );
        }
        return response()->json($response);
    }
}
