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

// Ottieni la bio del relatore dagli articoli del blog (author meta o post content)
$relatore_bio = '';
if ( $user_id ) {
    // Cerca la bio nella biografia utente
    $relatore_bio = get_user_meta( $user_id, 'description', true );

    // Se non c'è, cerca negli articoli (cerca una sezione "Chi è [nome]" o simile)
    if ( empty( $relatore_bio ) ) {
        $author_posts = get_posts([
            'author'         => $user_id,
            'post_type'      => 'post',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        if ( ! empty( $author_posts ) ) {
            $post_content = $author_posts[0]->post_content;
            // Cerca pattern bio in fondo all'articolo
            if ( preg_match( '/(?:Chi è|Biografia|About).*?<\/p>/si', $post_content, $matches ) ) {
                $relatore_bio = wp_strip_all_tags( $matches[0] );
            }
        }
    }
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
						<div class="ipv-relatore-info">
							<?php if ( $user_id ) :
								$user = get_userdata( $user_id );
								$avatar = get_avatar( $user_id, 120 );
								?>
								<div class="ipv-relatore-avatar">
									<?php echo $avatar; ?>
								</div>
							<?php endif; ?>
							<div class="ipv-relatore-details">
								<h1 class="ipv-relatore-name"><?php echo esc_html( $term->name ); ?></h1>
								<?php if ( ! empty( $relatore_bio ) ) : ?>
									<div class="ipv-relatore-bio">
										<?php echo wp_kses_post( wpautop( $relatore_bio ) ); ?>
									</div>
								<?php elseif ( $term->description ) : ?>
									<div class="ipv-relatore-bio">
										<?php echo wp_kses_post( wpautop( $term->description ) ); ?>
									</div>
								<?php endif; ?>
								<div class="ipv-relatore-stats">
									<span class="ipv-stat-item">
										<i class="fa fa-video-camera"></i>
										<strong><?php echo intval( $term->count ); ?></strong> Video
									</span>
									<?php
									// Conta i post del blog
									if ( $user_id ) {
										$posts_count = count_user_posts( $user_id, 'post' );
										if ( $posts_count > 0 ) :
											?>
											<span class="ipv-stat-item">
												<i class="fa fa-pencil"></i>
												<strong><?php echo intval( $posts_count ); ?></strong> Articoli
											</span>
										<?php endif;
									}
									?>
								</div>
							</div>
						</div>
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

						<!-- Video Wall filtrato per relatore -->
						<div class="ipv-relatore-video-wall">
							<?php
							// Aggiungi filtro temporaneo per il video wall
							add_filter( 'pre_get_posts', function( $query ) use ( $term ) {
								if ( $query->get( 'post_type' ) === 'video_ipv' && ! is_admin() ) {
									$tax_query = $query->get( 'tax_query' ) ?: [];
									$tax_query[] = [
										'taxonomy' => 'ipv_relatore',
										'field'    => 'term_id',
										'terms'    => $term->term_id,
									];
									$query->set( 'tax_query', $tax_query );
								}
							}, 10, 1 );

							// Render video wall shortcode
							echo do_shortcode( '[ipv_video_wall show_filters="no"]' );
							?>
						</div>
					</div>

					<!-- Tab Contenuto: Articoli -->
					<?php if ( $user_id && $posts_count > 0 ) : ?>
						<div class="ipv-tab-content" id="tab-articles">
							<h2 class="ipv-section-title">Articoli di <?php echo esc_html( $term->name ); ?></h2>

							<?php
							// Query articoli del relatore
							$articles_query = new WP_Query([
								'post_type'      => 'post',
								'author'         => $user_id,
								'posts_per_page' => 10,
								'paged'          => max( 1, get_query_var( 'paged' ) ),
							]);

							if ( $articles_query->have_posts() ) :
								?>
								<!-- Usa il template del tema Influencers per gli articoli -->
								<div class="bt-blog-post-content">
									<?php while ( $articles_query->have_posts() ) : $articles_query->the_post(); ?>
										<div class="bt-main-post">
											<?php
											// Usa il template part del tema se esiste
											if ( locate_template( 'content.php' ) ) {
												get_template_part( 'content' );
											} elseif ( locate_template( 'content-post.php' ) ) {
												get_template_part( 'content', 'post' );
											} else {
												// Fallback: struttura base come single post
												?>
												<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
													<?php
													// Featured image
													if ( has_post_thumbnail() ) {
														echo '<div class="bt-post-featured-image">';
														the_post_thumbnail( 'large' );
														echo '</div>';
													}

													// Title
													echo '<h2 class="bt-post-title"><a href="' . esc_url( get_permalink() ) . '">' . get_the_title() . '</a></h2>';

													// Meta info
													if ( function_exists( 'influencers_meta_render' ) ) {
														echo influencers_meta_render();
													} else {
														echo '<div class="bt-post-meta">';
														echo '<span class="bt-post-date">' . get_the_date() . '</span>';
														echo '</div>';
													}

													// Excerpt/Content
													echo '<div class="bt-post-excerpt">';
													the_excerpt();
													echo '</div>';

													// Read more
													echo '<a href="' . esc_url( get_permalink() ) . '" class="bt-read-more">Leggi di più</a>';
													?>
												</article>
												<?php
											}
											?>
										</div>
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

/* Video Wall nella pagina relatore */
.ipv-relatore-video-wall {
	margin-top: 20px;
}

/* Blog posts spacing */
.bt-blog-post-content {
	margin-top: 20px;
}

.bt-blog-post-content .bt-main-post {
	margin-bottom: 40px;
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
}
</style>

<?php get_footer(); ?>
