<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::create('vaccinations',function(Blueprint $t){$t->id();$t->foreignId('pet_id')->constrained()->restrictOnDelete();$t->string('vaccine_name',120);$t->date('date_administered')->nullable();$t->date('next_due_date')->nullable()->index();$t->text('notes')->nullable();$t->timestamps();});}public function down():void{Schema::dropIfExists('vaccinations');}};
