<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNumbersFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('numbers_files', function (Blueprint $table) {
            $table->bigIncrements('file_id');
            $table->string('file_hash_name')->unique();
            $table->string('original_file_path');
            $table->string('modified_file_path');
            $table->integer('total_numbers_count');
            $table->integer('valid_numbers_count');
            $table->integer('corrected_numbers_count');
            $table->integer('not_valid_numbers_count');
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
        Schema::dropIfExists('numbers_files');
    }
}
