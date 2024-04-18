<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Resources\OrderResource;
use App\Http\Resources\TransactionResource;

use App\Models\Order;
use App\Models\Transaction;

use App\Response\Status;
use App\Functions\GlobalFunction;
use Carbon\carbon;
use App\Http\Requests\Reports\DisplayRequest;

class ReportController extends Controller
{
    public function view(DisplayRequest $request)
    {
        $search = $request->input("search", "");
        $status = $request->input("status", "");
        $rows = $request->input("rows", 10);
        $paginate = $request->input("paginate", 1);
        $from = $request->from;
        $to = $request->to;
        $served_status = $request->served_status;

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d");

        $order = Transaction::with([
            "orders" => function ($query) {
                return $query->whereNull("deleted_at");
            },
        ])
            ->where(function ($query) use ($search) {
                $query
                    ->where("id", "like", "%" . $search . "%")
                    ->orWhere("date_ordered", "like", "%" . $search . "%")
                    ->orWhere("order_no", "like", "%" . $search . "%")
                    ->orWhere("date_needed", "like", "%" . $search . "%")
                    ->orWhere("date_approved", "like", "%" . $search . "%")
                    ->orWhere("company_name", "like", "%" . $search . "%")
                    ->orWhere("department_name", "like", "%" . $search . "%")
                    ->orWhere("location_name", "like", "%" . $search . "%")
                    ->orWhere("customer_name", "like", "%" . $search . "%")
                    ->orWhere("charge_department_name", "like", "%" . $search . "%")
                    ->orWhere("charge_location_name", "like", "%" . $search . "%")
                    ->orWhere("requestor_name", "like", "%" . $search . "%");
            })
            ->when(isset($request->from) && isset($request->to), function ($query) use (
                $from,
                $to
            ) {
                $query->where(function ($query) use ($from, $to) {
                    $query
                        ->whereDate("date_needed", ">=", $from)
                        ->whereDate("date_needed", "<=", $to);
                });
            })
            ->when($status === "today", function ($query) use ($date_today) {
                $query->whereNotNull("date_approved")->whereDate("date_needed", $date_today);
            })
            ->when($status === "pending", function ($query) use ($date_today) {
                $query->whereDate("date_needed", ">", $date_today)->whereNotNull("date_approved");
            })
            ->when($status === "all", function ($query) {
                $query->whereNotNull("date_needed")->whereNotNull("date_approved");
            })
            ->when($served_status === "served", function ($query) {
                $query->whereNotNull("date_served")->whereNotNull("date_approved");
            })
            ->when($served_status === "approved", function ($query) {
                $query->whereNull("date_served")->whereNotNull("date_approved");
            })
            ->orderByDesc("updated_at");

        $order = $paginate
            ? $order->orderByDesc("updated_at")->paginate($rows)
            : $order3
                ->orderByDesc("updated_at")
                ->with("orders")
                ->get();

        $is_empty = $order->isEmpty();
        if ($is_empty) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        TransactionResource::collection($order);

        return GlobalFunction::response_function(Status::ORDER_DISPLAY, $order);
    }

    public function serve(Request $request, $id)
    {
        $serve = Transaction::where("id", $id);

        $not_found = $serve->get()->first();
        if (!$not_found) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $serve->update([
            "date_served" => Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d"),
        ]);

        $serve = new TransactionResource($serve->get()->first());

        return GlobalFunction::response_function(Status::TRANSACTION_SERVE, $serve);
    }

    // public function return_status(Request $request, $id)
    // {
    //     $invalid_return = Transaction::where("id", $id)
    //         ->whereNull("date_served")
    //         ->whereNull("return")
    //         ->get();

    //     if ($invalid_return->isEmpty()) {
    //         return GlobalFunction::invalid(Status::INVALID_ACTION);
    //     }
    //     $serve = Transaction::where("id", $id)->whereNull("date_served");

    //     $not_found = $serve->get()->first();
    //     if (!$not_found) {
    //         return GlobalFunction::not_found(Status::NOT_FOUND);
    //     }

    //     $serve->update([
    //         "return" => $request["return"],
    //         "date_approved" => null,
    //     ]);

    //     $serve = new TransactionResource($serve->get()->first());

    //     return GlobalFunction::response_function(Status::TRANSACTION_RETURN, $serve);
    // }

    public function count(Request $request)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")

            ->format("Y-m-d");

        $today = Transaction::whereNotNull("date_approved")
            ->whereDate("date_needed", $date_today)
            ->get()
            ->count();

        $count = [
            "today" => $today,
        ];

        return GlobalFunction::response_function(Status::COUNT_DISPLAY, $count);
    }
    public function requestor_count(Request $request)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")

            ->format("Y-m-d");

        $requestor_id = Auth()->id();

        $approve = Transaction::whereNotNull("date_approved")
            ->where("requestor_id", $requestor_id)
            ->get()
            ->count();
        $disapprove = Transaction::onlyTrashed()
            ->whereNotNull("date_approved")
            ->where("requestor_id", $requestor_id)
            ->get()
            ->count();

        $count = [
            "approve" => $approve,
            "disapprove" => $disapprove,
        ];

        return GlobalFunction::response_function(Status::COUNT_DISPLAY, $count);
    }
    public function export(DisplayRequest $request)
    {
        $from = $request->from;
        $to = $request->to;
        $status = $request->input("status", "");
        $served_status = $request->served_status;

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d");

        $order = Order::with([
            "transaction" => function ($query) {
                return $query->withTrashed();
            },
        ])
            ->when($status === "today", function ($query) use ($date_today) {
                $query->whereHas("transaction", function ($query) use ($date_today) {
                    $query->whereNotNull("date_approved")->whereDate("date_needed", $date_today);
                });
            })
            ->when($status === "all", function ($query) use ($date_today) {
                $query->whereHas("transaction", function ($query) use ($date_today) {
                    $query->whereNotNull("date_approved");
                });
            })
            ->when($status === "pending", function ($query) use ($date_today) {
                $query->whereHas("transaction", function ($query) use ($date_today) {
                    $query
                        ->whereNotNull("date_approved")
                        ->whereDate("date_needed", ">", $date_today);
                });
            })
            ->when(isset($request->from) && isset($request->to), function ($query) use (
                $from,
                $to
            ) {
                $query->whereHas("transaction", function ($query) use ($from, $to) {
                    $query
                        ->whereDate("date_needed", ">=", $from)
                        ->whereDate("date_needed", "<=", $to);
                });
            })
            ->when($served_status === "served", function ($query) {
                $query->whereNotNull("date_served")->whereNotNull("date_approved");
            })
            ->when($served_status === "approved", function ($query) {
                $query->whereNull("date_served")->whereNotNull("date_approved");
            })
            ->get();
        return GlobalFunction::response_function(Status::DATA_EXPORT, $order);
    }
}
