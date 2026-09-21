<?php
/**
 * Standard card: image (3:2 or 16:9), optional kicker, headline, optional dek, meta.
 *
 * @package EurasiaPulse
 *
 * @var array $args {
 *   post        int|WP_Post
 *   ratio       '32'|'169'            default '32'
 *   size        registered image size default by ratio
 *   sizes       img sizes attribute
 *   heading     'h2'|'h3'             default 'h3'
 *   show_kicker bool                  default false
 *   show_dek    bool                  default false
 *   dek_words   int                   default 0 (subtitle / manual excerpt only)
 *   caption     bool                  default false
 *   meta        string[]              default [ 'region', 'category' ]
 *   class       string
 * }
 */

$eurasiapulse_post = get_post( $args['post'] ?? null );
if ( ! $eurasiapulse_post ) {
	return;
}
$eurasiapulse_ratio   = ( isset( $args['ratio'] ) && '169' === $args['ratio'] ) ? '169' : '32';
$eurasiapulse_size    = $args['size'] ?? ( '169' === $eurasiapulse_ratio ? 'ep-169-m' : 'ep-32-m' );
$eurasiapulse_sizes   = $args['sizes'] ?? '(min-width: 1240px) 390px, (min-width: 900px) 33vw, (min-width: 600px) 50vw, 100vw';
$eurasiapulse_heading = ( isset( $args['heading'] ) && 'h2' === $args['heading'] ) ? 'h2' : 'h3';
$eurasiapulse_meta    = $args['meta'] ?? array( 'region', 'category' );
$eurasiapulse_dek     = ! empty( $args['show_dek'] ) ? eurasiapulse_dek( $eurasiapulse_post, (int) ( $args['dek_words'] ?? 0 ) ) : '';
$eurasiapulse_class   = trim( 'card card--standard ' . ( $args['class'] ?? '' ) );
?>
<article class="<?php echo esc_attr( $eurasiapulse_class ); ?>">
	<?php
	echo eurasiapulse_figure( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
		$eurasiapulse_post,
		$eurasiapulse_ratio,
		$eurasiapulse_size,
		array(
			'sizes'   => $eurasiapulse_sizes,
			'caption' => ! empty( $args['caption'] ),
		)
	);
	if ( ! empty( $args['show_kicker'] ) ) {
		eurasiapulse_kicker( $eurasiapulse_post );
	}
	?>
	<<?php echo $eurasiapulse_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal. ?> class="headline"><a href="<?php echo esc_url( get_permalink( $eurasiapulse_post ) ); ?>"><?php echo esc_html( get_the_title( $eurasiapulse_post ) ); ?></a></<?php echo $eurasiapulse_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $eurasiapulse_dek ) : ?>
		<p class="dek"><?php echo esc_html( $eurasiapulse_dek ); ?></p>
	<?php endif; ?>
	<?php eurasiapulse_meta_line( $eurasiapulse_post, $eurasiapulse_meta ); ?>
</article>
