<?php
class IPV_Markdown_Full {
    public static function parse($text) {
        $html = $text;
        $html = preg_replace_callback('/```(.*?)```/s', fn($m)=>'<pre><code>'.htmlspecialchars($m[1]).'</code></pre>', $html);
        foreach ([6,5,4,3,2,1] as $h) {
            $pattern = '/^' . str_repeat('#',$h) . ' (.*)$/m';
            $html = preg_replace($pattern, '<h'.$h.'>$1</h'.$h.'>', $html);
        }
        $html = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $html);
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);
        $html = preg_replace('/^- (.*)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>\n?)+/', '<ul>$0</ul>', $html);
        return nl2br($html);
    }
}
?>