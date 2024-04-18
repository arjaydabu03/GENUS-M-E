<?php

namespace App\Http\Controllers\api;

use App\Models\UOM;
use App\Models\Category;
use App\Models\Material;
use App\Response\Status;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;

use App\Models\MaterialAccountTitle;
use App\Http\Requests\Material\DisplayRequest;
use App\Http\Requests\Material\MaterialRequest;
use App\Http\Requests\Material\Validation\CodeRequest;
use App\Http\Requests\Material\Validation\ImportRequest;

class MaterialController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $search = $request->search;
        $status = $request->status;
        $rows = $request->input("rows", 10);
        $paginate = $request->input("paginate", 1);

        $material = Material::with("category", "uom", "warehouse", "account_title")

            ->select("id", "code", "name", "category_id", "uom_id", "warehouse_id")
            ->when($paginate, function ($query) {
                $query->select(
                    "id",
                    "code",
                    "name",
                    "category_id",
                    "uom_id",
                    "warehouse_id",
                    "updated_at"
                );
            })
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })
            ->when($search, function ($query) use ($search) {
                $query
                    ->where("code", "like", "%" . $search . "%")
                    ->orWhere("name", "like", "%" . $search . "%")
                    ->orWhere("category_id", "like", "%" . $search . "%");
            });

        $material = $paginate
            ? $material->orderByDesc("updated_at")->paginate($rows)
            : $material->orderByDesc("updated_at")->get();

        $is_empty = $material->isEmpty();
        if ($is_empty) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }
        return GlobalFunction::response_function(Status::MATERIAL_DISPLAY, $material);
    }

    public function show($id)
    {
        $material = Material::with("category")
            ->where("id", $id)
            ->get();
        if ($material->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }
        return GlobalFunction::response_function(Status::MATERIAL_DISPLAY, $material->first());
    }

    public function store(MaterialRequest $request)
    {
        // return $request;
        $material = new Material([
            "code" => $request["code"],
            "name" => $request["name"],
            "cip_no" => $request["cip_no"],
            "helpdesk_no" => $request["helpdesk_no"],
            "category_id" => $request["category_id"],
            "uom_id" => $request["uom_id"],
            "warehouse_id" => $request["warehouse_id"],
        ]);
        $material->save();
        $account_title = $request["account_title"];

        foreach ($account_title as $key => $value) {
            MaterialAccountTitle::create([
                "material_id" => $material->id,
                "account_title_id" => $account_title[$key]["account_title_id"],
            ]);
        }
        $material = $material
            ->with("category", "uom", "warehouse", "account_title")
            ->firstWhere("id", $material->id);
        return GlobalFunction::save(Status::MATERIAL_SAVE, $material);
    }

    public function update(Request $request, $id)
    {
        $account_title = $request->account_title;

        $newTaggedAccount = collect($account_title)
            ->pluck("account_title_id")
            ->toArray();

        $currentTaggedAccount = MaterialAccountTitle::where("material_id", $id)
            ->get()
            ->pluck("account_title_id")
            ->toArray();

        foreach ($currentTaggedAccount as $account_title_id) {
            if (!in_array($account_title_id, $newTaggedAccount)) {
                MaterialAccountTitle::where("material_id", $id)
                    ->where("account_title_id", $account_title_id)
                    ->delete();
            }
        }
        foreach ($account_title as $index => $value) {
            if (!in_array($value["account_title_id"], $currentTaggedAccount)) {
                MaterialAccountTitle::create([
                    "material_id" => $id,
                    "account_title_id" => $value["account_title_id"],
                ]);
            }
        }
        $not_found = Material::where("id", $id)->get();

        if ($not_found->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $material = Material::find($id);

        $material->update([
            "code" => $request["code"],
            "name" => $request["name"],
            "cip_no" => $request["cip_no"],
            "helpdesk_no" => $request["helpdesk_no"],
            "category_id" => $request["category_id"],
            "uom_id" => $request["uom_id"],
            "warehouse_id" => $request["warehouse_id"],
        ]);

        $material = $material
            ->with("category", "uom", "account_title")
            ->firstWhere("id", $material->id);
        return GlobalFunction::response_function(Status::MATERIAL_UPDATE, $material);
    }

    public function destroy($id)
    {
        $material = Material::where("id", $id)
            ->withTrashed()
            ->get();

        if ($material->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $material = Material::withTrashed()->find($id);
        $is_active = Material::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $material->delete();
            $message = Status::ARCHIVE_STATUS;
        } else {
            $material->restore();
            $message = Status::RESTORE_STATUS;
        }
        return GlobalFunction::response_function($message, $material);
    }

    public function validate_code(CodeRequest $request)
    {
        return GlobalFunction::response_function(Status::SINGLE_VALIDATION);
    }

    public function import_material(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $file_import) {
            $code = $file_import["code"];
            $name = $file_import["name"];
            $uom = $file_import["uom"];
            $category = $file_import["category"];
            $warehouse = $file_import["warehouse"];
            $account_title = $file_import["account_title_id"];

            $category_id = Category::where("name", $category)->first();

            $uom_id = UOM::where("code", $uom)->first();

            $warehouse_id = Warehouse::where("name", $warehouse)->first();

            $account_title_id = Warehouse::where("name", $account_title)->first();

            $material = Material::create([
                "code" => $code,
                "name" => $name,
                "category_id" => $category_id->id,
                "uom_id" => $uom_id->id,
                "warehouse_id" => $warehouse_id->id,
                "account_title_id" => $account_title_id->sync_id,
            ]);
        }
        return GlobalFunction::save(
            Status::MATERIAL_IMPORT,
            $material->orderByDesc("created_at")->get()
        );
    }

    public function elixir_material(Request $request)
    {
        return $material = Material::with("category", "uom", "warehouse")->get();
    }
    public function elixir_pivot(Request $request)
    {
        return $material = MaterialAccountTitle::get();
    }
}
