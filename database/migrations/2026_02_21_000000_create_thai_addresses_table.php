<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('thai_addresses', function (Blueprint $table) {
            $table->id('id');
            $table->string('subdistrict_code'); // district_code
            $table->string('subdistrict'); // district
            $table->string('district_code'); // amphoe_code
            $table->string('district'); // amphoe
            $table->string('province_code'); // province_code
            $table->string('province'); // province
            $table->string('postal_code'); // zipcode
            $table->timestamps();
            $table->softDeletes();

            $table->index('subdistrict');
            $table->index('district');
            $table->index('province');
            $table->index('postal_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('thai_addresses');
    }
};
