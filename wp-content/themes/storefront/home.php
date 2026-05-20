<?php
/**
 * Custom Blog Layout - Storefront
 */

get_header();
?>

<div class="content-area">
    <main class="site-main">

        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>

                <article <?php post_class('sf-blog-post'); ?>>

                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="sf-featured-image">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('large'); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="sf-post-content">

                        <div class="sf-post-category">
                            <?php the_category(' '); ?>
                        </div>

                        <h2 class="sf-post-title">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_title(); ?>
                            </a>
                        </h2>

                        <div class="sf-post-meta">
                            <span><?php echo get_the_date(); ?></span>
                            <span>by <?php the_author(); ?></span>
                            <span><?php comments_number('No Comments', '1 Comment', '% Comments'); ?></span>
                        </div>

                        <div class="sf-post-excerpt">
                            <?php the_excerpt(); ?>
                        </div>

                        <a class="sf-read-more" href="<?php the_permalink(); ?>">
                            Read More
                        </a>

                    </div>

                </article>

            <?php endwhile; ?>

            <?php the_posts_pagination(); ?>

        <?php else : ?>
            <p>No posts found.</p>
        <?php endif; ?>

    </main>
</div>

<?php get_footer(); ?>
