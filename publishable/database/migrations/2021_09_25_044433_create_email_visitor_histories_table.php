<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailVisitorHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_visitor_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('email_log_id')->index('FK__email_log');
            $table->string('client_ip', 50);
            $table->dateTime('created_at');

            $table->foreign('email_log_id', 'FK_email_log')->references('id')->on('email_log')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_visitor_histories', function (Blueprint $table) {
            $table->dropForeign('FK_email_log');
        });
        Schema::dropIfExists('email_visitor_histories');
    }
}
