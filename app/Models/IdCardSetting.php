<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdCardSetting extends Model
{
    protected $fillable = [
        'school_id', 'public_exam_name', 'orientation', 'background_image',
        'card_width', 'card_height', 'photo_width', 'photo_height',
        'margin_top', 'margin_bottom', 'margin_left', 'margin_right', 'content_padding_top',
        'name_font_size', 'name_color', 'details_font_size', 'details_color',
        'row_spacing', 'show_principal_signature'
    ];
}
