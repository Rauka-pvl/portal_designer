<?php

return [
    'max_images' => (int) env('COMMUNITY_MAX_IMAGES', 10),
    'max_image_kb' => (int) env('COMMUNITY_MAX_IMAGE_KB', 5120),
    'max_text_length' => 2000,
    'max_comment_length' => 1000,
    'posts_per_page' => 10,
    'like_notify_window_minutes' => 60,
];
