<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomesTable extends Migration
{
    public function up()
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('record_hash', 40);

            $table->bigInteger('income_id')->nullable();
            $table->string('number')->nullable();
            $table->date('income_date')->nullable();
            $table->date('last_change_date')->nullable();
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->bigInteger('barcode')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->date('date_close')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->bigInteger('nm_id')->nullable();
            $table->timestamps();

            $table->unique(
                ['account_id', 'record_hash'],
                'incomes_account_record_hash_unique'
            );

            $table->index(
                ['account_id', 'income_date'],
                'incomes_account_income_date_index'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('incomes');
    }
}
