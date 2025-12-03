<?php
// MTP_ND/config.php - project-wide config

if (!defined('BASE_URL')) {
    // Đặt theo folder project trên localhost. Nếu tên folder khác thì đổi cho phù hợp.
    define('BASE_URL', '/MTP_ND');
}

if (!defined('AUTH_SECRET')) {
    // Thay bằng chuỗi random dài trước khi deploy
    define('AUTH_SECRET', 'replace_with_a_random_32+_char_secret!');
}