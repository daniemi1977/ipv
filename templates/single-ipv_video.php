<?php
get_header();
the_post();
$video_id = get_post_meta(get_the_ID(), '_ipv_video_id', true);
?>
<div class="ipv-wrapper">
  <?php if($video_id): ?>
    
<div class="ipv-embed-video">
<?php 
  $y = get_post_meta(get_the_ID(),'_ipv_youtube_link',true);
  if(!$y){
      $id = get_post_meta(get_the_ID(),'_ipv_youtube_id',true);
      if($id) $y = "https://www.youtube.com/embed/".$id;
  }
  if($y):
?>
  <div class="ipv-video-wrapper">
    <iframe width="100%" height="420" src="<?php echo esc_url($y); ?>" frameborder="0" allowfullscreen></iframe>
  </div>
<?php endif; ?>
</div>

  <?php endif; ?>
  
<div class="ipv-structural">
<hr>
<h3>Dati Strutturali</h3>
<ul>
  <li><strong>Durata:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_duration',true)); ?></li>
  <li><strong>ID YouTube:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_youtube_id',true)); ?></li>
  <li><strong>Pubblicato il:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_published_at',true)); ?></li>
  <li><strong>Visualizzazioni:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_views',true)); ?></li>
  <li><strong>Like:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_likes',true)); ?></li>
</ul>
<hr>
</div>

<hr class="ipv-sep">
<div class="ipv-structural">
<h3>Dati Strutturali</h3>
<ul>
  <li><strong>Durata:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_duration',true)); ?></li>
  <li><strong>ID YouTube:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_youtube_id',true)); ?></li>
  <li><strong>Pubblicato il:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_published_at',true)); ?></li>
  <li><strong>Visualizzazioni:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_views',true)); ?></li>
  <li><strong>Like:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_likes',true)); ?></li>
  <li><strong>Link YouTube:</strong> <?php echo esc_html(get_post_meta(get_the_ID(),'_ipv_youtube_link',true)); ?></li>
</ul>
</div>
<hr class="ipv-sep">
<div class="ipv-content markdown-notion">
    <?php echo apply_filters('the_content', get_post_meta(get_the_ID(), '_ipv_ai_description', true)); ?>
  </div>
</div>
<?php get_footer(); ?>
