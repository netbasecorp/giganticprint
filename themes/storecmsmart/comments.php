<?php
defined( 'ABSPATH' ) || exit;
if ( post_password_required() ) {
	echo '<p class="comment-password-notice">' . esc_html__( 'This post is password protected. Enter the password to view comments.', 'storecmsmart' ) . '</p>';
	return;
}
?>

<div class="comments-wrap">

  <?php if ( have_comments() ) : ?>
    <h3 class="comments-title">
      <?php
      $count = get_comments_number();
      printf(
        /* translators: %1$s: comment count, %2$s: post title */
        esc_html( _nx( '%1$s comment on &ldquo;%2$s&rdquo;', '%1$s comments on &ldquo;%2$s&rdquo;', $count, 'comments title', 'storecmsmart' ) ),
        number_format_i18n( $count ),
        '<span>' . get_the_title() . '</span>'
      );
      ?>
    </h3>

    <ol class="comment-list">
      <?php wp_list_comments( [ 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 48 ] ); ?>
    </ol>

    <?php the_comments_pagination( [
      'prev_text' => __( '← Older', 'storecmsmart' ),
      'next_text' => __( 'Newer →', 'storecmsmart' ),
    ] ); ?>

  <?php endif; ?>

  <?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
    <p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'storecmsmart' ); ?></p>
  <?php endif; ?>

  <?php
  comment_form( [
    'title_reply'          => __( 'Leave a comment', 'storecmsmart' ),
    'title_reply_before'   => '<h3 id="reply-title" class="comment-reply-title">',
    'title_reply_after'    => '</h3>',
    'comment_notes_before' => '',
    'label_submit'         => __( 'Post comment', 'storecmsmart' ),
    'class_submit'         => 'btn-primary',
  ] );
  ?>

</div>
