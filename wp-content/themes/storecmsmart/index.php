<?php get_header(); ?>

<main id="main-content" class="site-main">
  <?php do_action( 'giga_before_content' ); ?>

  <?php if ( ! is_front_page() ) :
    $blog_page_id = (int) get_option( 'page_for_posts' );
    $blog_title   = $blog_page_id ? get_the_title( $blog_page_id ) : __( 'Blog', 'storecmsmart' );
  ?>
  <div class="page-hero">
    <div class="container">
      <?php giga_breadcrumb(); ?>
      <h1 class="page-title"><?php echo esc_html( $blog_title ); ?></h1>
    </div>
  </div>
  <?php endif; ?>

  <div class="container">
    <?php if ( have_posts() ) : ?>
      <div class="posts-grid">
        <?php while ( have_posts() ) : the_post(); ?>
          <article <?php post_class( 'post-card' ); ?>>
            <?php if ( has_post_thumbnail() ) : ?>
              <a href="<?php the_permalink(); ?>" class="post-card-thumb">
                <?php the_post_thumbnail( 'medium_large' ); ?>
              </a>
            <?php endif; ?>
            <div class="post-card-body">
              <div class="post-card-meta"><?php echo get_the_date(); ?></div>
              <h2 class="post-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
              <div class="post-card-excerpt"><?php the_excerpt(); ?></div>
              <a href="<?php the_permalink(); ?>" class="btn-primary btn-sm"><?php esc_html_e( 'Read more', 'storecmsmart' ); ?></a>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
      <div class="pagination">
        <?php the_posts_pagination( [ 'prev_text' => __( '← Previous', 'storecmsmart' ), 'next_text' => __( 'Next →', 'storecmsmart' ) ] ); ?>
      </div>
    <?php else : ?>
      <div class="no-results">
        <h2><?php esc_html_e( 'Nothing found', 'storecmsmart' ); ?></h2>
        <p><?php esc_html_e( 'Try a search or check back later.', 'storecmsmart' ); ?></p>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
