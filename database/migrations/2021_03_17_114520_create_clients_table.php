<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClientsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('clients', function(Blueprint $table)
		{
            $table->increments('id');
            $table->string('guid')->nullable()->unique();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->integer('bonus_miles')->nullable()->default(0);
            $table->tinyInteger('bonus_status')->nullable()->default(0);
            $table->integer('mileage')->nullable()->default(0);
            $table->smallInteger('rental_days')->default(0);
            $table->boolean('registered')->default(0);
            $table->string('password', 60)->nullable();
            $table->string('comment', 10000)->nullable();
            $table->string('email')->nullable();
            $table->string('city_department', 6)->nullable();
            $table->tinyInteger('age')->nullable();
            $table->boolean('sex')->nullable()->default(0);
            $table->string('passport_series')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('passport_date_of_issue')->nullable();
            $table->string('passport_validity')->nullable();
            $table->string('passport_issued_by')->nullable();
            $table->string('passport_unit_code')->nullable();
            $table->string('driver_license_series')->nullable();
            $table->string('driver_license_number')->nullable();
            $table->string('driver_license_date_of_issues')->nullable();
            $table->string('driver_license_validity')->nullable();
            $table->timestamps();
            $table->integer('passport_blob_id')->nullable();
            $table->integer('driver_license_blob_id')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('clients');
	}

}
