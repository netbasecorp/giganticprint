<?php get_header(); ?>

<main id="main-content" class="site-main">
  <?php do_action( 'giga_before_content' ); ?>

  <div class="page-hero">
    <div class="container">
      <?php giga_breadcrumb(); ?>
      <h1 class="page-title"><?php the_archive_title(); ?></h1>
      <?php the_archive_description( '<p class="page-subtitle">', '</p>' ); ?>
    </div>
  </div>

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
            </div>
          </article>
        <?php endwhile; ?>
      </div>
      <div class="pagination">
        <?php the_posts_pagination( [ 'prev_text' => __( '← Previous', 'storecmsmart' ), 'next_text' => __( 'Next →', 'storecmsmart' ) ] ); ?>
      </div>
    <?php else : ?>
      <p><?php esc_html_e( 'No posts found.', 'storecmsmart' ); ?></p>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
