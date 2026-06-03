<?php get_header(); ?>

<main id="main-content" class="site-main">
  <?php do_action( 'giga_before_content' ); ?>

  <div class="page-hero">
    <div class="container">
      <?php giga_breadcrumb(); ?>
      <h1 class="page-title">
        <?php printf( wp_kses_post( __( 'Search results for: <em>%s</em>', 'storecmsmart' ) ), esc_html( get_search_query() ) ); ?>
      </h1>
    </div>
  </div>

  <div class="container">
    <?php get_search_form(); ?>

    <?php if ( have_posts() ) : ?>
      <div class="posts-grid" style="margin-top: 32px;">
        <?php while ( have_posts() ) : the_post(); ?>
          <article <?php post_class( 'post-card' ); ?>>
            <?php if ( has_post_thumbnail() ) : ?>
              <a href="<?php the_permalink(); ?>" class="post-card-thumb">
                <?php the_post_thumbnail( 'medium_large' ); ?>
              </a>
            <?php endif; ?>
            <div class="post-card-body">
              <h2 class="post-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
              <div class="post-card-excerpt"><?php the_excerpt(); ?></div>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
      <div class="pagination">
        <?php the_posts_pagination(); ?>
      </div>
    <?php else : ?>
      <p style="margin-top: 24px; color: var(--nbd-mb-text-soft);"><?php esc_html_e( 'No results found. Try a different keyword.', 'storecmsmart' ); ?></p>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
