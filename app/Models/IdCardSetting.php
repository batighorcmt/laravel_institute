<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdCardSetting extends Model
{
    protected $fillable = [
        'school_id', 'public_exam_name', 'language', 'orientation', 'background_image',
        'card_width', 'card_height', 'photo_width', 'photo_height',
        'margin_top', 'margin_bottom', 'margin_left', 'margin_right', 'content_padding_top',
        'name_font_size', 'name_color', 'details_font_size', 'details_color',
        'id_no_font_size', 'id_no_color',
        'row_spacing', 'show_principal_signature',
        'fields', 'show_school_header', 'custom_labels'
    ];

    protected $casts = [
        'fields' => 'array',
        'custom_labels' => 'array',
        'show_principal_signature' => 'boolean',
        'show_school_header' => 'boolean',
    ];
}
