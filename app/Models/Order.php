<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\carbon;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "order";

    protected $fillable = [
        "transaction_id",
        "requestor_id",

        "order_no",
        "customer_code",

        "material_id",
        "material_code",
        "material_name",

        "category_id",
        "category_name",

        "uom_id",
        "uom_code",
        "account_title_id",
        "account_title_code",
        "account_title_name",
        "plate_no",
        "quantity",
        "quantity_serve",
        "remarks",
    ];

    protected $hidden = ["created_at", "updated_at"];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
    public function time()
    {
        return $this->belongsTo(Order::class);
    }
    public function account_title()
    {
        return $this->belongsToMany(
            AccountTitle::class,
            "material_account_title",
            "material_id",
            "account_title_id",
            "material_id",
            "sync_id"
        );
    }
}
