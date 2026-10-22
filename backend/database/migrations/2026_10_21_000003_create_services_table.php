<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::create('services',function(Blueprint $t){$t->id();$t->string('name',120);$t->text('description')->nullable();$t->decimal('price',10,2)->default(0);$t->unsignedSmallInteger('duration_minutes')->default(30);$t->boolean('is_available')->default(true)->index();$t->timestamps();});}public function down():void{Schema::dropIfExists('services');}};
