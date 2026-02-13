<?php

use Lonate\Core\Database\Schema\Schema;
use Lonate\Core\Database\Schema\Blueprint;

class CreateUsersTable
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150);
            $table->string('password', 255);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
