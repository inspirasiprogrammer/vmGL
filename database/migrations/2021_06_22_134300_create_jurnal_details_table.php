<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJurnalDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jurnal_details', function (Blueprint $table) {
            $table->id();
            $table->integer('jurnal_id');
            $table->integer('account_id');
            $table->integer('cost_id')->default(0);
            $table->decimal('debet',18,2)->default(0);
            $table->decimal('credit',18,2)->default(0);
            $table->string('memo',120)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jurnal_details');
    }
}
