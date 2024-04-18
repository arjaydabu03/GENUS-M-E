<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("order", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("transaction_id")->index();
            $table
                ->foreign("transaction_id")
                ->references("id")
                ->on("transactions")
                ->onDelete("cascade");

            $table->unsignedInteger("requestor_id")->index();
            $table
                ->foreign("requestor_id")
                ->references("id")
                ->on("users");

            $table->integer("order_no");

            $table->string("customer_code");

            $table->integer("material_id");
            $table->string("material_code");
            $table->string("material_name");

            $table->integer("category_id");
            $table->string("category_name");

            $table->integer("uom_id");
            $table->string("uom_code");
            $table->unsignedBigInteger("account_title_id")->index();
            $table
                ->foreign("account_title_id")
                ->references("sync_id")
                ->on("account_title");
            $table->string("account_title_code");
            $table->string("account_title_name");

            $table->string("plate_no")->nullable();
            $table->double("quantity");
            $table->double("quantity_serve")->nullable();
            $table->string("remarks")->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("order");
    }
};
