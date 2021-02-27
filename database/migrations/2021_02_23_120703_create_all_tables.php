<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllTables extends Migration
{
    public function up()
    {
      Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // User name
        $table->string('avatar')->default('default.png'); // User photo, default.png
        $table->string('email')->unique(); // User email, unique table users
        $table->string('password'); // User password
      });

      Schema::create('userfavorites', function (Blueprint $table) {
        $table->id();
        $table->integer('id_user'); // Id do Usuário
        $table->integer('id_barber'); // Id do Barbeiro
      });

      Schema::create('userappointments', function (Blueprint $table) {
        $table->id();
        $table->integer('id_user'); // id user to appointment barber
        $table->integer('id_barber'); // ID barber
        $table->integer('id_service'); // Id service barber
        $table->dateTime('ap_datetime'); // Datetime from appointment to user from barber
      });

      Schema::create('barbers', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // name barber
        $table->string('avatar')->default('default.png'); // photo barber, default.png
        $table->float('stars')->default(0); // Nota do barbeiro, start 0
        $table->string('latitude')->nullable();
        $table->string('longitude')->nullable();
      });

      Schema::create('barberphotos', function (Blueprint $table) {
        $table->id();
        $table->integer('id_barber'); // Idenfifier Barber by id
        $table->string('url'); // Photo barber
      });

      Schema::create('barberreviews', function (Blueprint $table) {
          $table->id();
          $table->integer('id_barber'); // Idenfifier Barber
          $table->float('rate'); // Note from barber
      });

      Schema::create('barberservices', function (Blueprint $table) {
        $table->id();
        $table->integer('id_barber'); // Idenfifier Barber
        $table->string('name'); // Name service of barber
        $table->float('price'); // Barber Pricing Service
      });

      Schema::create('barbertestimonials', function (Blueprint $table) {
        $table->id();
        $table->integer('id_barber'); // Idenfifier Barber
        $table->string('name'); // Name client tertimonial
        $table->float('rate'); // Note drom barber
        $table->string('body'); // The Body testimonial
      });

      Schema::create('barberavailability', function (Blueprint $table) {
        $table->id();
        $table->integer('id_barber');
        $table->integer('weekday'); // Day week of job the barber
        $table->text('hours'); // Hours of days of job the barber
      });

    }

    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('userfavorites');
        Schema::dropIfExists('userappointments');
        Schema::dropIfExists('barbers');
        Schema::dropIfExists('barberphotos');
        Schema::dropIfExists('barberreviews');
        Schema::dropIfExists('barberservices');
        Schema::dropIfExists('barbertestimonials');
        Schema::dropIfExists('barberavailability');
    }
}
