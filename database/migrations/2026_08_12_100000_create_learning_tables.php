<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Progress is tracked per anonymous visitor (a UUID in a cookie) so the
        // site can be shared as a link with no signup step.
        Schema::create('learner_progress', function (Blueprint $table) {
            $table->id();
            $table->string('learner_id', 36)->index();
            $table->string('module_slug');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['learner_id', 'module_slug']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('learner_id', 36)->index();
            $table->string('module_slug');
            $table->unsignedTinyInteger('score');
            $table->unsignedTinyInteger('total');
            $table->timestamps();
        });

        // Fake business data. The tool-calling lesson lets Claude query this,
        // exactly the way it would query a real orders table.
        Schema::create('demo_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('status');
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('placed_on');
            $table->date('expected_on')->nullable();
            $table->string('carrier')->nullable();
            $table->timestamps();
        });

        // Documents for the RAG playground. A null learner_id means the shared,
        // seeded handbook that everybody searches by default.
        Schema::create('demo_documents', function (Blueprint $table) {
            $table->id();
            $table->string('learner_id', 36)->nullable()->index();
            $table->string('title');
            $table->longText('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_documents');
        Schema::dropIfExists('demo_orders');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('learner_progress');
    }
};
