<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas del control de elementos.
 *
 *  - items: elementos consumibles o devolutivos. stock = disponibles en almacén;
 *    unavailable_stock = devueltos dañados o incompletos (en almacén pero no se prestan).
 *  - entries / entry_details: entradas (suman stock).
 *  - deliveries / delivery_details: salidas y préstamos (restan stock).
 *  - item_returns / item_return_details: devoluciones de devolutivos con su estado.
 *  - movements: historial (kárdex) de todo lo que mueve el stock; de aquí salen los reportes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nit', 40)->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('email')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('logo')->nullable();
            $table->string('keeper_name')->nullable();
            $table->string('keeper_title')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nit', 40)->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('dependencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('head_name')->nullable();
            $table->string('phone', 60)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('responsibles', function (Blueprint $table) {
            $table->id();
            $table->string('document', 30)->unique();
            $table->string('name');
            $table->string('position')->nullable();
            $table->foreignId('dependency_id')->constrained()->restrictOnDelete();
            $table->string('phone', 60)->nullable();
            $table->string('email')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->string('type', 12); // CONSUMABLE | RETURNABLE
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('unit', 30)->default('Unidad');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('unavailable_stock')->default(0);
            $table->unsignedInteger('min_stock')->default(0);
            $table->string('serial', 100)->nullable();
            $table->string('location', 120)->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['type', 'active']);
            $table->index('name');
        });

        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('provider_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('document', 120)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 10)->default('VALID');
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index('date');
        });

        Schema::create('entry_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->foreignId('responsible_id')->constrained()->restrictOnDelete();
            $table->foreignId('dependency_id')->constrained()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 10)->default('VALID');
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index('date');
            $table->index('due_date');
        });

        Schema::create('delivery_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('type', 12); // tipo del elemento al momento de la salida
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('returned')->default(0);
        });

        Schema::create('item_returns', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('delivery_id')->constrained()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->index('date');
        });

        Schema::create('item_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_detail_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('condition', 12); // GOOD | DAMAGED | INCOMPLETE
            $table->string('notes')->nullable();
        });

        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->unsignedInteger('quantity');
            $table->integer('stock_after');
            $table->integer('unavailable_after')->default(0);
            $table->string('condition', 12)->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dependency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_return_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['date', 'type']);
        });
    }

    public function down(): void
    {
        foreach (['movements', 'item_return_details', 'item_returns', 'delivery_details', 'deliveries',
            'entry_details', 'entries', 'items', 'responsibles', 'dependencies', 'providers', 'categories', 'organizations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
