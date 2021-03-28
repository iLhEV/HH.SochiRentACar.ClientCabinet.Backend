<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('rents', function(Blueprint $table)
		{
            $table->increments('id');
            $table->uuid('guid')->unique();
            $table->integer('client_id');
            $table->uuid('client_guid')->nullable();
            $table->integer('client_duplicate_id')->nullable()->default(0);
            $table->uuid('client_duplicate_guid')->nullable();
            $table->uuid('car_guid')->nullable();
            $table->timestamp('date_begin')->nullable();
            $table->timestamp('date_end')->nullable();
            $table->mediumInteger('sum');
            $table->smallInteger('rental_days');
            $table->integer('mileage');
            $table->integer('mileage_difference')->nullable();
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
		Schema::drop('rents');
	}

}
