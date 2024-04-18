<?php

namespace App\Http\Controllers\Api;

use App\Response\Status;
use App\Models\AccountTitle;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;

class AccountTitleController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status;
        $search = $request->search;
        $paginate = isset($request->paginate) ? $request->paginate : 1;

        $account_title = AccountTitle::when($status === "inactive", function ($query) {
            $query->where("status", "0");
        })->when($search, function ($query) use ($search) {
            $query
                ->where("code", "like", "%" . $search . "%")
                ->orWhere("name", "like", "%" . $search . "%");
        });

        $account_title = $paginate
            ? $account_title->orderByDesc("updated_at")->paginate($request->rows)
            : $account_title->orderByDesc("updated_at")->get();

        $is_empty = $account_title->isEmpty();

        if ($is_empty) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        return GlobalFunction::response_function(Status::ACCOUNT_TITLE_DISPLAY, $account_title);
    }
    public function store(Request $request)
    {
        $sync = $request->all();

        $account_title = AccountTitle::upsert($sync, ["sync_id"], ["code", "name", "status"]);

        return GlobalFunction::save(Status::SYNC_ACCOUNT_TITLE, $request->toArray());
    }
}
