<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClientsDuplicatesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('clients_duplicates', function(Blueprint $table)
		{
            $table->increments('id');
            $table->integer('client_id');
            $table->uuid('guid')->unique('guid');
            $table->boolean('duplicate_reason');
            $table->string('city_department', 50);
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('comment', 10000)->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
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
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('clients_duplicates');
	}

}
