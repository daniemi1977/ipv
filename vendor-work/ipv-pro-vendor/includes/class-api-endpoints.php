<?php
/**
 * API Endpoints - VENDOR
 * REST API che riceve richieste dai client
 */

if (!defined('ABSPATH')) exit;

class IPV_Vendor_API_Endpoints {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        $namespace = 'ipv-vendor/v1';
        
        // License validation
        register_rest_route($namespace, '/license/validate', [
            'methods' => 'POST',
            'callback' => [$this, 'validate_license'],
            'permission_callback' => '__return_true'
        ]);
        
        // License deactivation
        register_rest_route($namespace, '/license/deactivate', [
            'methods' => 'POST',
            'callback' => [$this, 'deactivate_license'],
            'permission_callback' => '__return_true'
        ]);
        
        // Transcript request
        register_rest_route($namespace, '/transcript', [
            'methods' => 'POST',
            'callback' => [$this, 'get_transcript'],
            'permission_callback' => '__return_true'
        ]);
        
        // AI description
        register_rest_route($namespace, '/ai/description', [
            'methods' => 'POST',
            'callback' => [$this, 'get_ai_description'],
            'permission_callback' => '__return_true'
        ]);
        
        // YouTube data
        register_rest_route($namespace, '/youtube/video-data', [
            'methods' => 'POST',
            'callback' => [$this, 'get_youtube_data'],
            'permission_callback' => '__return_true'
        ]);
        
        // ✅ NEW: Golden Prompt - Verify License
        register_rest_route($namespace, '/golden-prompt/verify', [
            'methods' => 'POST',
            'callback' => [$this, 'verify_golden_prompt_license'],
            'permission_callback' => '__return_true'
        ]);
        
        // ✅ NEW: Golden Prompt - Fetch Content
        register_rest_route($namespace, '/golden-prompt/fetch', [
            'methods' => 'POST',
            'callback' => [$this, 'fetch_golden_prompt'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * Validate License
     */
    public function validate_license($request) {
        $params = $request->get_json_params();
        
        $license_key = $params['license_key'] ?? $this->extract_license_from_headers($request);
        $domain = $params['domain'] ?? $this->extract_domain_from_headers($request);
        
        if (empty($license_key)) {
            return new WP_Error('missing_license', 'License key required', array('status' => 400));
        }
        
        if (empty($domain)) {
            return new WP_Error('missing_domain', 'Domain required', array('status' => 400));
        }
        
        $manager = IPV_Vendor_License_Manager::get_instance();
        $result = $manager->validate($license_key, $domain);
        
        if (!$result['valid']) {
            error_log("[IPV Vendor] Validation failed: {$result['error']}");
            return new WP_Error($result['error'], 'License validation failed', ['status' => 401]);
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Deactivate License
     * 
     * Endpoint: POST /license/deactivate
     * Body: { license_key, site_url }
     */
    public function deactivate_license($request) {
        $params = $request->get_json_params();
        
        $license_key = $params['license_key'] ?? '';
        $site_url = $params['site_url'] ?? '';
        
        // Validate parameters
        if (empty($license_key)) {
            error_log('[IPV Vendor] Deactivate request missing license_key');
            return new WP_Error('missing_license', 'License key required', array('status' => 400));
        }
        
        if (empty($site_url)) {
            error_log('[IPV Vendor] Deactivate request missing site_url');
            return new WP_Error('missing_site_url', 'Site URL required', array('status' => 400));
        }
        
        // Call license manager deactivate
        $manager = IPV_Vendor_License_Manager::get_instance();
        $result = $manager->deactivate($license_key, $site_url);
        
        // Handle result
        if (!$result['success']) {
            error_log("[IPV Vendor] Deactivation failed: {$result['error']} - {$result['message']}");
            return new WP_Error(
                $result['error'], 
                $result['message'], 
                array('status' => 401)
            );
        }
        
        error_log("[IPV Vendor] License deactivated successfully: $license_key");
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => $result['message'],
            'license_key' => $result['license_key']
        ));
    }
    
    /**
     * Get Transcript
     */
    public function get_transcript($request) {
        // Authenticate
        $auth = $this->authenticate($request);
        if (is_wp_error($auth)) {
            return $auth;
        }
        
        $license = $auth['license'];
        $params = $request->get_json_params();
        $video_id = $params['video_id'] ?? '';
        
        if (empty($video_id)) {
            return new WP_Error('missing_video_id', 'Video ID required', array('status' => 400));
        }
        
        // Check cache
        $cached = $this->get_from_cache($video_id, 'transcript');
        if ($cached) {
            error_log("[IPV Vendor] Transcript cache hit: $video_id");
            return rest_ensure_response([
                'transcript' => $cached,
                'cached' => true,
                'credits_remaining' => $license->credits_total - $license->credits_used
            ]);
        }
        
        // Check credits
        $credit_mgr = IPV_Vendor_Credit_Manager::get_instance();
        if (!$credit_mgr->has_credits($license->id, 1)) {
            return new WP_Error('insufficient_credits', 'Insufficient credits', ['status' => 402]);
        }
        
        // Get transcript from external API (SupaData)
        $transcript = $this->fetch_transcript_external($video_id);
        
        if (is_wp_error($transcript)) {
            return $transcript;
        }
        
        // Deduct credit
        $deduct_result = $credit_mgr->deduct($license->id, 'transcript', 1, ['video_id' => $video_id]);
        
        if (!$deduct_result['success']) {
            return new WP_Error('deduction_failed', $deduct_result['error'], ['status' => 500]);
        }
        
        // Cache it
        $this->save_to_cache($video_id, 'transcript', $transcript);
        
        return rest_ensure_response([
            'transcript' => $transcript,
            'credits_remaining' => $deduct_result['balance']
        ]);
    }
    
    /**
     * Get AI Description
     */
    public function get_ai_description($request) {
        // Authenticate
        $auth = $this->authenticate($request);
        if (is_wp_error($auth)) {
            return $auth;
        }
        
        $license = $auth['license'];
        $params = $request->get_json_params();
        
        $video_id = $params['video_id'] ?? '';
        $transcript = $params['transcript'] ?? '';
        
        if (empty($transcript)) {
            return new WP_Error('missing_transcript', 'Transcript required', array('status' => 400));
        }
        
        // Check credits
        $credit_mgr = IPV_Vendor_Credit_Manager::get_instance();
        if (!$credit_mgr->has_credits($license->id, 1)) {
            return new WP_Error('insufficient_credits', 'Insufficient credits', ['status' => 402]);
        }
        
        // Generate AI description (OpenAI)
        $description = $this->generate_ai_description($transcript);
        
        if (is_wp_error($description)) {
            return $description;
        }
        
        // Deduct credit
        $credit_mgr->deduct($license->id, 'ai_description', 1, ['video_id' => $video_id]);
        
        return rest_ensure_response([
            'description' => $description
        ]);
    }
    
    /**
     * Get YouTube Data
     */
    public function get_youtube_data($request) {
        $auth = $this->authenticate($request);
        if (is_wp_error($auth)) {
            return $auth;
        }
        
        $params = $request->get_json_params();
        $video_id = $params['video_id'] ?? '';
        
        if (empty($video_id)) {
            return new WP_Error('missing_video_id', 'Video ID required', array('status' => 400));
        }
        
        // Get from YouTube API
        $data = $this->fetch_youtube_data($video_id);
        
        if (is_wp_error($data)) {
            return $data;
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Authenticate request
     */
    private function authenticate($request) {
        $license_key = $this->extract_license_from_headers($request);
        $domain = $this->extract_domain_from_headers($request);
        
        if (empty($license_key)) {
            error_log("[IPV Vendor] No license key in request");
            return new WP_Error('unauthorized', 'License key required', ['status' => 401]);
        }
        
        $manager = IPV_Vendor_License_Manager::get_instance();
        $result = $manager->validate($license_key, $domain);
        
        if (!$result['valid']) {
            error_log("[IPV Vendor] Auth failed: {$result['error']}");
            return new WP_Error('unauthorized', 'Unauthorized: ' . $result['error'], ['status' => 401]);
        }
        
        return $result;
    }
    
    /**
     * Extract license from headers (multiple methods for compatibility)
     */
    private function extract_license_from_headers($request) {
        // Method 1: Authorization header
        $auth = $request->get_header('Authorization');
        if ($auth && preg_match('/Bearer\s+(\S+)/', $auth, $matches)) {
            return $matches[1];
        }
        
        // Method 2: X-License-Key header
        $license = $request->get_header('X-License-Key');
        if ($license) {
            return $license;
        }
        
        // Method 3: X-API-Key header
        $api_key = $request->get_header('X-API-Key');
        if ($api_key) {
            return $api_key;
        }
        
        // Method 4: Body
        $params = $request->get_json_params();
        if (isset($params['license_key'])) {
            return $params['license_key'];
        }
        
        return '';
    }
    
    /**
     * Extract domain from headers
     */
    private function extract_domain_from_headers($request) {
        $site_url = $request->get_header('X-Site-URL');
        if ($site_url) {
            return parse_url($site_url, PHP_URL_HOST);
        }
        
        $params = $request->get_json_params();
        if (isset($params['domain'])) {
            return $params['domain'];
        }
        
        return '';
    }
    
    /**
     * Cache helpers
     */
    private function get_from_cache($video_id, $type) {
        global $wpdb;
        
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT data FROM {$wpdb->prefix}ipv_cache WHERE video_id = %s AND type = %s",
            $video_id, $type
        ));
        
        return $row ? $row->data : null;
    }
    
    private function save_to_cache($video_id, $type, $data) {
        global $wpdb;
        
        $wpdb->replace(
            $wpdb->prefix . 'ipv_cache',
            [
                'video_id' => $video_id,
                'type' => $type,
                'data' => $data
            ],
            ['%s', '%s', '%s']
        );
    }
    
    /**
     * External API calls
     */
    private function fetch_transcript_external($video_id) {
        $api_key = get_option('ipv_vendor_supadata_api_key', '');
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'SupaData API key not configured');
        }
        
        $response = wp_remote_post('https://api.supadata.ai/v1/transcript', [
            'headers' => ['Authorization' => 'Bearer ' . $api_key],
            'body' => json_encode(['video_id' => $video_id]),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['transcript'])) {
            return $data['transcript'];
        }
        
        return new WP_Error('api_error', 'Failed to get transcript');
    }
    
    private function generate_ai_description($transcript) {
        $api_key = get_option('ipv_vendor_openai_api_key', '');
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'OpenAI API key not configured');
        }
        
        // Simplified - would call OpenAI API here
        return "AI generated description based on transcript...";
    }
    
    private function fetch_youtube_data($video_id) {
        $api_key = get_option('ipv_vendor_youtube_api_key', '');
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'YouTube API key not configured');
        }
        
        $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id={$video_id}&key={$api_key}";
        
        $response = wp_remote_get($url, ['timeout' => 15]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['items'][0])) {
            return $data['items'][0]['snippet'];
        }
        
        return new WP_Error('not_found', 'Video not found');
    }
    
    /**
     * ✅ Verify Golden Prompt License
     */
    public function verify_golden_prompt_license($request) {
        $params = $request->get_json_params();
        
        $license_key = $params['license_key'] ?? '';
        $domain = $params['domain'] ?? $this->extract_domain_from_headers($request);
        
        if (empty($license_key)) {
            error_log('[IPV Vendor] Golden Prompt verify: Missing license_key');
            return new WP_Error('missing_license', 'License key required', ['status' => 400]);
        }
        
        if (empty($domain)) {
            error_log('[IPV Vendor] Golden Prompt verify: Missing domain');
            return new WP_Error('missing_domain', 'Domain required', ['status' => 400]);
        }
        
        // Verifica licenza
        $valid = IPV_Vendor_Golden_Prompt_Manager::verify_license($license_key, $domain);
        
        if (!$valid) {
            error_log("[IPV Vendor] Golden Prompt verify FAILED: {$license_key} for {$domain}");
            return new WP_Error('invalid_license', 'Golden Prompt license invalid or domain mismatch', ['status' => 401]);
        }
        
        error_log("[IPV Vendor] Golden Prompt verify SUCCESS: {$license_key} for {$domain}");
        
        return rest_ensure_response([
            'valid' => true,
            'license_key' => $license_key,
            'domain' => $domain,
            'message' => 'Golden Prompt license verified successfully'
        ]);
    }
    
    /**
     * ✅ Fetch Golden Prompt Content
     */
    public function fetch_golden_prompt($request) {
        $params = $request->get_json_params();
        
        $license_key = $params['license_key'] ?? '';
        $domain = $params['domain'] ?? $this->extract_domain_from_headers($request);
        
        if (empty($license_key)) {
            error_log('[IPV Vendor] Golden Prompt fetch: Missing license_key');
            return new WP_Error('missing_license', 'License key required', ['status' => 400]);
        }
        
        // Verifica licenza prima di fetch
        $valid = IPV_Vendor_Golden_Prompt_Manager::verify_license($license_key, $domain);
        
        if (!$valid) {
            error_log("[IPV Vendor] Golden Prompt fetch: Invalid license {$license_key}");
            return new WP_Error('invalid_license', 'Golden Prompt license invalid', ['status' => 401]);
        }
        
        // Fetch content
        $content = IPV_Vendor_Golden_Prompt_Manager::fetch_golden_prompt($license_key);
        
        if (empty($content)) {
            error_log("[IPV Vendor] Golden Prompt fetch: No content for {$license_key}");
            return new WP_Error('no_content', 'Golden Prompt content not available', ['status' => 404]);
        }
        
        error_log("[IPV Vendor] Golden Prompt fetch SUCCESS: {$license_key}");
        
        return rest_ensure_response([
            'success' => true,
            'prompt' => $content,
            'encrypted' => false, // Già decriptato lato server
            'license_key' => $license_key
        ]);
    }
}
