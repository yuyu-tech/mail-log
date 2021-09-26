<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('message_unique_id', 100)->unique('message_unique_id');
            $table->string('mailable')->nullable();
            $table->unsignedBigInteger('reference_model_id')->nullable();
            $table->json('data')->nullable();
            $table->string('subject');
            $table->longText('body');
            $table->string('from', 100);
            $table->string('from_name', 100)->nullable();
            $table->string('reply_to', 100)->nullable();
            $table->string('reply_to_name', 100)->nullable();
            $table->json('to')->nullable();
            $table->longText('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->json('filtered_to')->nullable();
            $table->json('filtered_cc')->nullable();
            $table->json('filtered_bcc')->nullable();
            $table->tinyInteger('is_address_modified')->default(0);
            $table->string('content_type', 50)->nullable();
            $table->text('headers')->nullable();
            $table->tinyInteger('status')->nullable()->default(1);
            $table->longText('attachments')->nullable();
            $table->text('error')->nullable();
            $table->dateTime('date');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_log');
    }
}
