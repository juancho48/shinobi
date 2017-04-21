<?php

use Illuminate\Database\Migrations\Migration;

class AddRoleTypeToRoleUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('role_user', function ($table) {
            $table->string('role_type')->nullable()->default(null)->after('role_on');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('role_user', function ($table) {
            $table->dropColumn('role_type');
        });
    }
}
