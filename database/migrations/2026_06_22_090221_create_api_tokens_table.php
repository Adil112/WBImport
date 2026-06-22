<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiTokensTable extends Migration
{
    public function up()
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('api_service_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('token_type_id')
                ->constrained()
                ->restrictOnDelete();

            $table->text('credentials');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique([
                'account_id',
                'api_service_id',
                'token_type_id',
            ]);
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_tokens');
    }
}
