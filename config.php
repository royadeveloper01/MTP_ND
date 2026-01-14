<?php
// MTP_ND/config.php - project-wide config

if (!defined('BASE_URL')) {
    // Calculate BASE_URL relative to the server document root
    $doc_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $dir_root = rtrim(str_replace('\\', '/', __DIR__), '/');
    
    // Remove doc_root from dir_root to get the relative path
    $base = str_replace($doc_root, '', $dir_root);
    define('BASE_URL', $base);
}