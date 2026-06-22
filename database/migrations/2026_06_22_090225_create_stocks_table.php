<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStocksTable extends Migration
{
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('record_hash', 40);

            $table->date('stock_date')->nullable();
            $table->date('last_change_date')->nullable();
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->bigInteger('barcode')->nullable();
            $table->integer('quantity')->nullable();
            $table->boolean('is_supply')->nullable();
            $table->boolean('is_realization')->nullable();
            $table->integer('quantity_full')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->integer('in_way_to_client')->nullable();
            $table->integer('in_way_from_client')->nullable();
            $table->bigInteger('nm_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->bigInteger('sc_code')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedInteger('discount')->nullable();
            $table->timestamps();

            $table->unique(
                ['account_id', 'record_hash'],
                'stocks_account_record_hash_unique'
            );

            $table->index(
                ['account_id', 'stock_date'],
                'stocks_account_stock_date_index'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('stocks');
    }
}
