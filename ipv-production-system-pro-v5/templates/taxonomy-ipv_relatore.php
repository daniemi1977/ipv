<?php
/**
 * Template per pagina relatore - Compatibile con tema Influencers
 * Mostra i video e i post del blog del relatore
 */

get_header();

// Ottieni il termine corrente
$term = get_queried_object();

// Cerca l'utente associato al relatore
$user_id = null;
$users = get_users([
    'meta_key'   => '_ipv_relatore_term_id',
    'meta_value' => $term->term_id,
    'number'     => 1,
]);

if ( ! empty( $users ) ) {
    $user_id = $users[0]->ID;
}

// Verifica se esiste il titlebar del tema
if ( function_exists( 'get_template_part' ) ) {
    get_template_part( 'framework/templates/site', 'titlebar' );
}
?>

<main id="bt_main" class="bt-site-main ipv-relatore-page">
	<div class="bt-main-content-ss">
		<div class="bt-container">
			<div class="bt-main-post-row">
				<div class="bt-main-post-col">

					<!-- Intestazione Relatore -->
					<div class="ipv-relatore-header">
						<?php if ( $user_id ) :
							$user = get_userdata( $user_id );
							$avatar = get_avatar( $user_id, 120 );
							$bio = get_user_meta( $user_id, 'description', true );
							?>
							<div class="ipv-relatore-info">
								<div class="ipv-relatore-avatar">
									<?php echo $avatar; ?>
								</div>
								<div class="ipv-relatore-details">
									<h1 class="ipv-relatore-name"><?php echo esc_html( $term->name ); ?></h1>
									<?php if ( $bio ) : ?>
										<div class="ipv-relatore-bio">
											<?php echo wp_kses_post( wpautop( $bio ) ); ?>
										</div>
									<?php endif; ?>
									<div class="ipv-relatore-stats">
										<span class="ipv-stat-item">
											<i class="fa fa-video-camera"></i>
											<strong><?php echo intval( $term->count ); ?></strong> Video
										</span>
										<?php
										// Conta i post del blog
										$posts_count = count_user_posts( $user_id, 'post' );
										if ( $posts_count > 0 ) :
											?>
											<span class="ipv-stat-item">
												<i class="fa fa-pencil"></i>
												<strong><?php echo intval( $posts_count ); ?></strong> Articoli
											</span>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php else : ?>
							<div class="ipv-relatore-info">
								<h1 class="ipv-relatore-name"><?php echo esc_html( $term->name ); ?></h1>
								<?php if ( $term->description ) : ?>
									<div class="ipv-relatore-bio">
										<?php echo wp_kses_post( wpautop( $term->description ) ); ?>
									</div>
								<?php endif; ?>
								<div class="ipv-relatore-stats">
									<span class="ipv-stat-item">
										<i class="fa fa-video-camera"></i>
										<strong><?php echo intval( $term->count ); ?></strong> Video
									</span>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<!-- Tab Navigation -->
					<div class="ipv-relatore-tabs">
						<button class="ipv-tab-btn active" data-tab="videos">
							<i class="fa fa-video-camera"></i> Video
						</button>
						<?php if ( $user_id && $posts_count > 0 ) : ?>
							<button class="ipv-tab-btn" data-tab="articles">
								<i class="fa fa-pencil"></i> Articoli
							</button>
						<?php endif; ?>
					</div>

					<!-- Tab Contenuto: Video -->
					<div class="ipv-tab-content active" id="tab-videos">
						<h2 class="ipv-section-title">Video di <?php echo esc_html( $term->name ); ?></h2>

						<?php
						// Query video
						$videos_query = new WP_Query([
							'post_type'      => 'video_ipv',
							'posts_per_page' => 12,
							'tax_query'      => [
								[
									'taxonomy' => 'ipv_relatore',
									'field'    => 'term_id',
									'terms'    => $term->term_id,
								],
							],
						]);

						if ( $videos_query->have_posts() ) :
							?>
							<div class="ipv-videos-grid">
								<?php while ( $videos_query->have_posts() ) : $videos_query->the_post();
									$video_id = get_post_meta( get_the_ID(), '_ipv_video_id', true );
									$thumbnail = get_post_meta( get_the_ID(), '_ipv_yt_thumbnail_url', true );
									$duration = get_post_meta( get_the_ID(), '_ipv_yt_duration_formatted', true );
									$views = get_post_meta( get_the_ID(), '_ipv_yt_view_count', true );
									$youtube_url = get_post_meta( get_the_ID(), '_ipv_youtube_url', true );

									if ( empty( $thumbnail ) && has_post_thumbnail() ) {
										$thumbnail = get_the_post_thumbnail_url( get_the_ID(), 'large' );
									}
									?>
									<div class="ipv-video-item">
										<a href="<?php echo esc_url( get_permalink() ); ?>" class="ipv-video-link">
											<div class="ipv-video-thumbnail">
												<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php the_title_attribute(); ?>">
												<?php if ( $duration ) : ?>
													<span class="ipv-video-duration"><?php echo esc_html( $duration ); ?></span>
												<?php endif; ?>
												<div class="ipv-video-overlay">
													<span class="ipv-play-icon">▶</span>
												</div>
											</div>
											<h3 class="ipv-video-title"><?php the_title(); ?></h3>
											<?php if ( $views ) : ?>
												<div class="ipv-video-views">
													<i class="fa fa-eye"></i> <?php echo esc_html( number_format_i18n( $views ) ); ?> visualizzazioni
												</div>
											<?php endif; ?>
										</a>
									</div>
								<?php endwhile; wp_reset_postdata(); ?>
							</div>

							<?php
							// Paginazione
							if ( $videos_query->max_num_pages > 1 ) :
								?>
								<div class="ipv-pagination">
									<?php
									echo paginate_links([
										'total'   => $videos_query->max_num_pages,
										'current' => max( 1, get_query_var( 'paged' ) ),
									]);
									?>
								</div>
							<?php endif; ?>
						<?php else : ?>
							<p>Nessun video disponibile per questo relatore.</p>
						<?php endif; ?>
					</div>

					<!-- Tab Contenuto: Articoli -->
					<?php if ( $user_id && $posts_count > 0 ) : ?>
						<div class="ipv-tab-content" id="tab-articles">
							<h2 class="ipv-section-title">Articoli di <?php echo esc_html( $term->name ); ?></h2>

							<?php
							// Query articoli
							$articles_query = new WP_Query([
								'post_type'      => 'post',
								'author'         => $user_id,
								'posts_per_page' => 10,
							]);

							if ( $articles_query->have_posts() ) :
								?>
								<div class="ipv-articles-list">
									<?php while ( $articles_query->have_posts() ) : $articles_query->the_post(); ?>
										<article class="ipv-article-item">
											<?php if ( has_post_thumbnail() ) : ?>
												<div class="ipv-article-thumb">
													<a href="<?php the_permalink(); ?>">
														<?php the_post_thumbnail( 'medium' ); ?>
													</a>
												</div>
											<?php endif; ?>
											<div class="ipv-article-content">
												<h3 class="ipv-article-title">
													<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
												</h3>
												<div class="ipv-article-meta">
													<span class="ipv-article-date">
														<i class="fa fa-calendar"></i> <?php echo get_the_date(); ?>
													</span>
													<?php if ( comments_open() || get_comments_number() ) : ?>
														<span class="ipv-article-comments">
															<i class="fa fa-comments"></i> <?php comments_number( '0', '1', '%' ); ?>
														</span>
													<?php endif; ?>
												</div>
												<div class="ipv-article-excerpt">
													<?php the_excerpt(); ?>
												</div>
												<a href="<?php the_permalink(); ?>" class="ipv-read-more">
													Leggi di più <i class="fa fa-arrow-right"></i>
												</a>
											</div>
										</article>
									<?php endwhile; wp_reset_postdata(); ?>
								</div>

								<?php
								// Paginazione articoli
								if ( $articles_query->max_num_pages > 1 ) :
									?>
									<div class="ipv-pagination">
										<?php
										echo paginate_links([
											'total'   => $articles_query->max_num_pages,
											'current' => max( 1, get_query_var( 'paged' ) ),
										]);
										?>
									</div>
								<?php endif; ?>
							<?php else : ?>
								<p>Nessun articolo disponibile.</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

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

<script>
jQuery(document).ready(function($) {
	$('.ipv-tab-btn').on('click', function() {
		var tab = $(this).data('tab');

		$('.ipv-tab-btn').removeClass('active');
		$(this).addClass('active');

		$('.ipv-tab-content').removeClass('active');
		$('#tab-' + tab).addClass('active');
	});
});
</script>

<style>
/* Relatore Header */
.ipv-relatore-header {
	background: #fff;
	padding: 30px;
	border-radius: 8px;
	margin-bottom: 30px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.ipv-relatore-info {
	display: flex;
	gap: 25px;
	align-items: flex-start;
}

.ipv-relatore-avatar img {
	border-radius: 50%;
	border: 4px solid #3498db;
}

.ipv-relatore-details {
	flex: 1;
}

.ipv-relatore-name {
	margin: 0 0 15px 0;
	font-size: 32px;
	color: #2c3e50;
}

.ipv-relatore-bio {
	margin-bottom: 15px;
	color: #555;
	line-height: 1.6;
}

.ipv-relatore-stats {
	display: flex;
	gap: 20px;
}

.ipv-stat-item {
	display: flex;
	align-items: center;
	gap: 6px;
	color: #7f8c8d;
}

.ipv-stat-item strong {
	color: #3498db;
	font-size: 18px;
}

/* Tabs */
.ipv-relatore-tabs {
	display: flex;
	gap: 10px;
	margin-bottom: 25px;
	border-bottom: 2px solid #e3e3e3;
}

.ipv-tab-btn {
	padding: 12px 24px;
	border: none;
	background: transparent;
	cursor: pointer;
	font-size: 16px;
	font-weight: 600;
	color: #7f8c8d;
	border-bottom: 3px solid transparent;
	transition: all 0.3s ease;
}

.ipv-tab-btn:hover {
	color: #3498db;
}

.ipv-tab-btn.active {
	color: #3498db;
	border-bottom-color: #3498db;
}

.ipv-tab-content {
	display: none;
}

.ipv-tab-content.active {
	display: block;
}

.ipv-section-title {
	font-size: 24px;
	margin-bottom: 25px;
	color: #2c3e50;
}

/* Videos Grid */
.ipv-videos-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
	gap: 25px;
	margin-bottom: 30px;
}

.ipv-video-item {
	background: #fff;
	border-radius: 8px;
	overflow: hidden;
	box-shadow: 0 2px 8px rgba(0,0,0,0.08);
	transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.ipv-video-item:hover {
	transform: translateY(-5px);
	box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

.ipv-video-link {
	text-decoration: none;
	color: inherit;
}

.ipv-video-thumbnail {
	position: relative;
	padding-top: 56.25%;
	background: #000;
	overflow: hidden;
}

.ipv-video-thumbnail img {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.ipv-video-duration {
	position: absolute;
	bottom: 10px;
	right: 10px;
	background: rgba(0,0,0,0.8);
	color: #fff;
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: 600;
}

.ipv-video-overlay {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0,0,0,0.4);
	display: flex;
	align-items: center;
	justify-content: center;
	opacity: 0;
	transition: opacity 0.3s ease;
}

.ipv-video-item:hover .ipv-video-overlay {
	opacity: 1;
}

.ipv-play-icon {
	font-size: 48px;
	color: #fff;
}

.ipv-video-title {
	padding: 15px;
	font-size: 16px;
	line-height: 1.4;
	margin: 0;
	color: #2c3e50;
}

.ipv-video-views {
	padding: 0 15px 15px 15px;
	font-size: 13px;
	color: #7f8c8d;
}

/* Articles List */
.ipv-articles-list {
	display: flex;
	flex-direction: column;
	gap: 25px;
	margin-bottom: 30px;
}

.ipv-article-item {
	display: flex;
	gap: 20px;
	background: #fff;
	padding: 20px;
	border-radius: 8px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.ipv-article-thumb {
	flex: 0 0 200px;
}

.ipv-article-thumb img {
	width: 100%;
	border-radius: 6px;
}

.ipv-article-content {
	flex: 1;
}

.ipv-article-title {
	margin: 0 0 10px 0;
	font-size: 20px;
}

.ipv-article-title a {
	color: #2c3e50;
	text-decoration: none;
}

.ipv-article-title a:hover {
	color: #3498db;
}

.ipv-article-meta {
	display: flex;
	gap: 15px;
	margin-bottom: 12px;
	font-size: 13px;
	color: #7f8c8d;
}

.ipv-article-excerpt {
	margin-bottom: 12px;
	color: #555;
	line-height: 1.6;
}

.ipv-read-more {
	color: #3498db;
	text-decoration: none;
	font-weight: 600;
}

.ipv-read-more:hover {
	text-decoration: underline;
}

/* Pagination */
.ipv-pagination {
	display: flex;
	justify-content: center;
	margin: 30px 0;
}

.ipv-pagination .page-numbers {
	padding: 8px 14px;
	margin: 0 4px;
	background: #fff;
	border: 1px solid #e3e3e3;
	border-radius: 4px;
	color: #2c3e50;
	text-decoration: none;
}

.ipv-pagination .page-numbers:hover,
.ipv-pagination .page-numbers.current {
	background: #3498db;
	color: #fff;
	border-color: #3498db;
}

/* Responsive */
@media (max-width: 768px) {
	.ipv-relatore-info {
		flex-direction: column;
		text-align: center;
	}

	.ipv-relatore-stats {
		justify-content: center;
	}

	.ipv-videos-grid {
		grid-template-columns: 1fr;
	}

	.ipv-article-item {
		flex-direction: column;
	}

	.ipv-article-thumb {
		flex: 0 0 auto;
	}
}
</style>

<?php get_footer(); ?>
