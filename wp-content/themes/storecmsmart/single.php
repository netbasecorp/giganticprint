<?php get_header(); ?>

<main id="main-content" class="site-main">
  <?php do_action( 'giga_before_content' ); ?>

  <?php while ( have_posts() ) : the_post(); ?>
    <div class="page-hero">
      <div class="container">
        <?php giga_breadcrumb(); ?>
        <div class="post-meta-top"><?php echo get_the_date(); ?> · <?php the_author(); ?></div>
        <h1 class="page-title"><?php the_title(); ?></h1>
      </div>
    </div>

    <?php if ( has_post_thumbnail() ) : ?>
      <div class="single-thumb container">
        <?php the_post_thumbnail( 'large' ); ?>
      </div>
    <?php endif; ?>

    <div class="container page-content-wrap">
      <div class="entry-content">
        <?php the_content(); ?>
      </div>
      <?php comments_template(); ?>
    </div>
  <?php endwhile; ?>
</main>

<?php get_footer(); ?>
