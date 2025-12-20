<?php
/**
 * IPV Production System Pro - PHP 8.1+ Safe Helpers
 * 
 * Anti-deprecated helpers per evitare warning con valori null
 * 
 * @package IPV_Production_System_Pro
 * @version 10.5.2
 * @since 10.5.2
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
 * Verifica se un valore è "N/A" o equivalente
 * 
 * @param mixed $value Valore da verificare
 * @return bool True se è N/A
 */
if (!function_exists('ipv_is_na')) {
    function ipv_is_na($value): bool {
        if (empty($value)) {
            return true;
        }
        $str = ipv_safe_string($value);
        $upper = strtoupper(trim($str));
        return $upper === 'N/A' || $upper === 'NA' || $upper === '';
    }
}

/**
 * Normalizza titolo YouTube eliminando N/A
 * 
 * @param mixed $title Titolo da normalizzare
 * @param string $video_id ID video per fallback
 * @return string|null Titolo valido o null
 */
if (!function_exists('ipv_normalize_title')) {
    function ipv_normalize_title($title, string $video_id = ''): ?string {
        $title = trim(ipv_safe_string($title));
        
        if (ipv_is_na($title)) {
            return $video_id ? 'YouTube Video ' . $video_id : null;
        }
        
        return $title;
    }
}
