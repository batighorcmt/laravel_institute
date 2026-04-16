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
        Schema::create('id_card_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('public_exam_name')->nullable();
            
            $table->string('orientation')->default('portrait');
            $table->longText('background_image')->nullable(); 
            
            // Design Layout (mm)
            $table->float('card_width')->default(54); 
            $table->float('card_height')->default(86); 
            $table->float('photo_width')->default(22);
            $table->float('photo_height')->default(26);
            
            // Margins / Offsets (mm)
            $table->float('margin_top')->default(5);
            $table->float('margin_bottom')->default(5);
            $table->float('margin_left')->default(5);
            $table->float('margin_right')->default(5);
            $table->float('content_padding_top')->default(32); // To skip BG header
            
            // Font Styles
            $table->integer('name_font_size')->default(11);
            $table->string('name_color')->default('#000000');
            $table->integer('details_font_size')->default(9);
            $table->string('details_color')->default('#333333');
            
            $table->integer('row_spacing')->default(2);
            
            // Labels
            $table->boolean('show_principal_signature')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_card_settings');
    }
};
