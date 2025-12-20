<?php
/**
 * IPV Pro Vendor - PHP 8.1+ Safe Helpers
 * 
 * Anti-deprecated helpers per evitare warning con valori null
 * 
 * @package IPV_Pro_Vendor
 * @version 1.6.4
 * @since 1.6.4
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Restituisce stringa safe per funzioni string PHP
 * Elimina deprecated PHP 8.1: strpos/str_replace/sanitize con null
 * 
 * @param mixed $value Valore da convertire
 * @return string Sempre una stringa (vuota se null)
 */
if (!function_exists('ipv_safe_string')) {
    function ipv_safe_string($value): string {
        return is_string($value) ? $value : '';
    }
}

/**
 * Restituisce array safe
 * 
 * @param mixed $value Valore da convertire
 * @return array Sempre un array (vuoto se null)
 */
if (!function_exists('ipv_safe_array')) {
    function ipv_safe_array($value): array {
        return is_array($value) ? $value : [];
    }
}

/**
 * Sanitizza in modo safe
 * 
 * @param mixed $value Valore da sanitizzare
 * @return string Stringa sanitizzata
 */
if (!function_exists('ipv_safe_sanitize')) {
    function ipv_safe_sanitize($value): string {
        return sanitize_text_field(ipv_safe_string($value));
    }
}

/**
 * Parse authorization header in modo robusto
 * 
 * @return string API key o stringa vuota
 */
if (!function_exists('ipv_parse_auth_header')) {
    function ipv_parse_auth_header(): string {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $auth = trim(str_replace('Bearer', '', ipv_safe_string($auth)));
        return $auth;
    }
}
