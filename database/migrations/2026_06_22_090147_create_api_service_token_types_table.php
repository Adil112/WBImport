<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiServiceTokenTypesTable extends Migration
{
    public function up()
    {
        Schema::create('api_service_token_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_service_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('token_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unique([
                'api_service_id',
                'token_type_id',
            ]);
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_service_token_types');
    }
}
