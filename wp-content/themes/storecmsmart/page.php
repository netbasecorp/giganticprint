<?php get_header(); ?>

<main id="main-content" class="site-main">
  <?php do_action( 'giga_before_content' ); ?>

  <?php while ( have_posts() ) : the_post(); ?>
    <div class="page-hero">
      <div class="container">
        <?php giga_breadcrumb(); ?>
        <h1 class="page-title"><?php the_title(); ?></h1>
      </div>
    </div>

    <div class="container page-content-wrap">
      <div class="entry-content">
        <?php the_content(); ?>
      </div>
    </div>
  <?php endwhile; ?>
</main>

<?php get_footer(); ?>
