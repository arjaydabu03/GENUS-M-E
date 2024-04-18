<?php

namespace App\Http\Controllers\Api;

use App\Models\Assets;
use App\Response\Status;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Asset\StoreRequest;
use App\Http\Requests\Asset\ImportRequest;
use App\Http\Requests\UOM\Validation\DisplayRequest;

class AssetController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $search = $request->search;
        $paginate = isset($request->paginate) ? $request->paginate : 1;

        $assets = Assets::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })->where(function ($query) use ($search) {
            $query
                ->where("asset_tag", "like", "%" . $search . "%")
                ->orWhere("description", "like", "%" . $search . "%");
        });

        $assets = $paginate
            ? $assets->orderByDesc("updated_at")->paginate($request->rows)
            : $assets->orderByDesc("updated_at")->get();

        $is_empty = $assets->isEmpty();

        if ($is_empty) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        return GlobalFunction::response_function(Status::ASSET_DISPLAY, $assets);
    }

    public function show($id)
    {
        $asset = Assets::where("id", $id)->get();

        if ($asset->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }
        return GlobalFunction::response_function(Status::ASSET_DISPLAY, $asset->first());
    }

    public function store(StoreRequest $request)
    {
        $asset = Assets::create([
            "asset_tag" => $request["asset_tag"],
            "description" => $request["description"],
        ]);
        return GlobalFunction::save(Status::ASSET_SAVE, $asset);
    }

    public function update(StoreRequest $request, $id)
    {
        $asset = Assets::find($id);

        $not_found = Assets::where("id", $id)->get();

        if ($not_found->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }
        $asset->update([
            "asset_tag" => $request["asset_tag"],
            "description" => $request["description"],
        ]);

        return GlobalFunction::response_function(Status::ASSETS_UPDATE, $asset);
    }

    public function destroy($id)
    {
        $asset = Assets::where("id", $id)
            ->withTrashed()
            ->get();

        if ($asset->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $asset = Assets::withTrashed()->find($id);
        $is_active = Assets::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $asset->delete();
            $message = Status::ARCHIVE_STATUS;
        } else {
            $asset->restore();
            $message = Status::RESTORE_STATUS;
        }
        return GlobalFunction::response_function($message, $asset);
    }
    public function import_assets(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $file_import) {
            $asset_tag = $file_import["asset_tag"];
            $description = $file_import["description"];

            $assets = Assets::create([
                "asset_tag" => $asset_tag,
                "description" => $description,
            ]);
        }
        return GlobalFunction::save(
            Status::ASSET_IMPORT,
            $assets->orderByDesc("created_at")->get()
        );
    }
}
