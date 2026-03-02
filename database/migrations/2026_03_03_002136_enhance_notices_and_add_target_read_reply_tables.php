<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->string('audience_type', 50)->default('all')->after('body');
            $table->boolean('reply_required')->default(false)->after('audience_type');
            $table->timestamp('expiry_at')->nullable()->after('publish_at');
            $table->string('attachment_path')->nullable()->after('reply_required');
        });

        Schema::create('notice_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notice_id')->constrained()->onDelete('cascade');
            $table->morphs('targetable'); // targetable_type, targetable_id
            $table->timestamps();
        });

        Schema::create('notice_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notice_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['notice_id', 'user_id']);
        });

        Schema::create('notice_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notice_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('voice_path');
            $table->integer('duration')->nullable()->comment('Duration in seconds');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notice_replies');
        Schema::dropIfExists('notice_reads');
        Schema::dropIfExists('notice_targets');
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['audience_type', 'reply_required', 'expiry_at', 'attachment_path']);
        });
    }
};
