<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialAccountTitle extends Model
{
    use HasFactory;

    protected $table = "material_account_title";

    protected $fillable = ["material_id", "account_title_id"];

    public function material()
    {
        return $this->belongsTo(Material::class, "material_id", "id");
    }
    public function account_title()
    {
        return $this->belongsTo(AccountTitle::class, "account_title_id", "sync_id");
    }
}
