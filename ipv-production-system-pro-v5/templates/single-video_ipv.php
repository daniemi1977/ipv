<?php
/**
 * Template singolo video - Compatibile con tema Influencers
 * Segue la struttura del single post del tema
 */

get_header();

// Verifica se esiste il titlebar del tema
if ( function_exists( 'get_template_part' ) ) {
    get_template_part( 'framework/templates/site', 'titlebar' );
}
?>

<main id="bt_main" class="bt-site-main ipv-single-video-main">
	<div class="bt-main-content-ss">
		<div class="bt-container">
			<div class="bt-main-post-row">
				<div class="bt-main-post-col">
					<?php
						while ( have_posts() ) : the_post();
							$video_id = get_post_meta( get_the_ID(), '_ipv_video_id', true );
							$youtube_url = get_post_meta( get_the_ID(), '_ipv_youtube_url', true );
							$published_at = get_post_meta( get_the_ID(), '_ipv_yt_published_at', true );
							$views = get_post_meta( get_the_ID(), '_ipv_yt_view_count', true );
							$duration = get_post_meta( get_the_ID(), '_ipv_yt_duration_formatted', true );
							?>
							<div class="bt-main-post">
								<article id="post-<?php the_ID(); ?>" <?php post_class('ipv-single-video'); ?>>

									<!-- Video Embed -->
									<?php if ( $video_id ) : ?>
										<div class="ipv-video-embed-wrapper">
											<div class="ipv-video-embed">
												<iframe width="100%" height="500"
													src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>"
													frameborder="0"
													allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
													allowfullscreen>
												</iframe>
											</div>
										</div>
									<?php endif; ?>

									<!-- Video Meta Info -->
									<div class="ipv-video-meta-info">
										<!-- Link YouTube URL -->
										<?php if ( $youtube_url ) : ?>
											<div class="ipv-video-youtube-link">
												<strong>🔗 Link YouTube:</strong>
												<a href="<?php echo esc_url( $youtube_url ); ?>" target="_blank" rel="noopener" class="ipv-youtube-url">
													<?php echo esc_html( $youtube_url ); ?>
												</a>
											</div>
										<?php endif; ?>

										<!-- Dati strutturali -->
										<div class="ipv-video-meta-data">
											<?php if ( $published_at ) : ?>
												<span class="ipv-meta-date">
													<i class="fa fa-calendar"></i> <?php echo esc_html( date_i18n( get_option('date_format'), strtotime( $published_at ) ) ); ?>
												</span>
											<?php endif; ?>

											<?php if ( $views ) : ?>
												<span class="ipv-meta-views">
													<i class="fa fa-eye"></i> <?php echo esc_html( number_format_i18n( $views ) ); ?> visualizzazioni
												</span>
											<?php endif; ?>

											<?php if ( $duration ) : ?>
												<span class="ipv-meta-duration">
													<i class="fa fa-clock-o"></i> <?php echo esc_html( $duration ); ?>
												</span>
											<?php endif; ?>
										</div>
									</div>

									<!-- Content -->
									<div class="entry-content">
										<?php the_content(); ?>
									</div>

									<!-- Taxonomies -->
									<div class="ipv-video-taxonomies">
										<?php
										// Relatori
										$relatori = get_the_terms( get_the_ID(), 'ipv_relatore' );
										if ( $relatori && ! is_wp_error( $relatori ) ) :
											?>
											<div class="ipv-tax-section">
												<strong>Relatori:</strong>
												<?php foreach ( $relatori as $relatore ) : ?>
													<a href="<?php echo esc_url( get_term_link( $relatore ) ); ?>" class="ipv-tax-link">
														<?php echo esc_html( $relatore->name ); ?>
													</a>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>

										<?php
										// Argomenti
										$argomenti = get_the_terms( get_the_ID(), 'ipv_argomento' );
										if ( $argomenti && ! is_wp_error( $argomenti ) ) :
											?>
											<div class="ipv-tax-section">
												<strong>Argomenti:</strong>
												<?php foreach ( $argomenti as $argomento ) : ?>
													<a href="<?php echo esc_url( get_term_link( $argomento ) ); ?>" class="ipv-tax-link">
														<?php echo esc_html( $argomento->name ); ?>
													</a>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>

										<?php
										// Anno
										$anni = get_the_terms( get_the_ID(), 'ipv_anno' );
										if ( $anni && ! is_wp_error( $anni ) ) :
											?>
											<div class="ipv-tax-section">
												<strong>Anno:</strong>
												<?php foreach ( $anni as $anno ) : ?>
													<a href="<?php echo esc_url( get_term_link( $anno ) ); ?>" class="ipv-tax-link">
														<?php echo esc_html( $anno->name ); ?>
													</a>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>

									<!-- File e Slide Scaricabili -->
									<?php
									$files = get_post_meta( get_the_ID(), '_ipv_files', true );
									if ( ! empty( $files ) && is_array( $files ) ) :
										?>
										<div class="ipv-video-files">
											<h3 class="ipv-files-title">
												<i class="fa fa-download"></i> Materiale Scaricabile
											</h3>
											<div class="ipv-files-grid">
												<?php foreach ( $files as $file ) :
													$file_url = isset( $file['url'] ) ? $file['url'] : '';
													$file_name = isset( $file['name'] ) ? $file['name'] : '';
													$file_size = isset( $file['size'] ) ? size_format( $file['size'], 2 ) : '';
													$file_type = isset( $file['type'] ) ? strtoupper( $file['type'] ) : '';
													?>
													<a href="<?php echo esc_url( $file_url ); ?>" class="ipv-file-download" download target="_blank">
														<div class="ipv-file-icon">
															<i class="fa fa-file-pdf-o"></i>
														</div>
														<div class="ipv-file-details">
															<div class="ipv-file-name"><?php echo esc_html( $file_name ); ?></div>
															<div class="ipv-file-meta">
																<?php if ( $file_type ) : ?><span class="ipv-file-type"><?php echo esc_html( $file_type ); ?></span><?php endif; ?>
																<?php if ( $file_size ) : ?><span class="ipv-file-size"><?php echo esc_html( $file_size ); ?></span><?php endif; ?>
															</div>
														</div>
														<div class="ipv-file-action">
															<i class="fa fa-download"></i>
														</div>
													</a>
												<?php endforeach; ?>
											</div>
										</div>
									<?php endif; ?>
								</article>
							</div>
							<?php

							// Tags (se esistono funzioni del tema)
							if ( function_exists( 'influencers_tags_render' ) ) {
								echo influencers_tags_render();
							}

							// Social Share (se esiste funzione del tema)
							if ( function_exists( 'influencers_share_render' ) ) {
								echo influencers_share_render();
							}

							// Author Box (se esiste funzione del tema)
							if ( function_exists( 'influencers_author_render' ) ) {
								echo influencers_author_render();
							}

							// Related Posts (se esiste funzione del tema)
							if ( function_exists( 'influencers_related_posts' ) ) {
								echo influencers_related_posts();
							}

							// Comments
							if ( comments_open() || get_comments_number() ) {
								comments_template();
							}
						endwhile;
					?>
				</div>

				<!-- Sidebar -->
				<div class="bt-sidebar-col">
					<div class="bt-sidebar">
						<?php
						if ( is_active_sidebar('main-sidebar') ) {
							dynamic_sidebar('main-sidebar');
						} elseif ( is_active_sidebar('sidebar-1') ) {
							dynamic_sidebar('sidebar-1');
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	// Social Media Channels (se esiste nel tema)
	if ( function_exists( 'get_template_part' ) ) {
		get_template_part( 'framework/templates/social', 'media-channels' );
	}
	?>
</main>

<?php get_footer(); ?>
