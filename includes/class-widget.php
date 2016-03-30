<?php

/**
 * Class SearchWP_Live_Search_Widget
 *
 * The SearchWP Live Ajax Search Widget
 *
 * @since 1.0
 */
class SearchWP_Live_Search_Widget extends WP_Widget {

	/**
	 * Register the Widget with WordPrss
	 */
	function __construct() {
		parent::__construct(
			'searchwp_live_search',
			__( 'SearchWP Live Search', 'swplas' ),
			array( 'description' => __( 'SearchWP Live Search', 'swplas' ), )
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );

		$destination    = empty( $instance['destination'] ) ? '' : esc_url( $instance['destination'] );
		$placeholder    = empty( $instance['placeholder'] ) ? __( 'Search for...', 'searchwp' ) : esc_attr( $instance['placeholder'] );
		$engine         = empty( $instance['engine'] ) ? 'default' : esc_attr( $instance['engine'] );
		$config         = empty( $instance['config'] ) ? 'default' : esc_attr( $instance['config'] );

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		?>
			<?php do_action( 'searchwp_live_search_widget_before_form' ); ?>
			<form role="search" method="get" class="searchwp-live-search-widget-search-form" action="<?php echo $destination; ?>">
				<?php do_action( 'searchwp_live_search_widget_before_field' ); ?>
				<label>
					<span class="screen-reader-text"><?php _e( 'Search for:', 'swplas' ); ?></span>
					<input type="search" class="search-field" placeholder="<?php echo $placeholder; ?>" value="" name="swpquery" data-swplive="true" data-swpengine="<?php echo $engine; ?>" data-swpconfig="<?php echo $config; ?>" title="<?php echo $placeholder; ?>" autocomplete="off">
				</label>
				<?php do_action( 'searchwp_live_search_widget_after_field' ); ?>
				<input type="submit" class="search-submit" value="<?php _e( 'Search', 'swplas' ); ?>">
				<?php do_action( 'searchwp_live_search_widget_after_submit' ); ?>
			</form>
			<?php do_action( 'searchwp_live_search_widget_after_form' ); ?>
		<?php

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 *
	 * @return string|void
	 */
	public function form( $instance ) {

		$widget_title       = isset( $instance['title'] ) ? $instance['title'] : __( 'Search', 'swplas' );
		$widget_placeholder = isset( $instance['placeholder'] ) ? $instance['placeholder'] : __( 'Search for...', 'swplas' );
		$widget_destination = isset( $instance['destination'] ) ? $instance['destination'] : '';

		// we'll piggyback SearchWP itself to pull a list of search engines
		$widget_engine = isset( $instance['engine'] ) ? $instance['engine'] : 'default';
		$engines = array();
		if ( class_exists( 'SearchWP' ) ) {
			$engines['default'] = 'Default';
			$searchwp = SearchWP::instance();
			$searchwp_engines = $searchwp->settings['engines'];
			foreach ( $searchwp_engines as $engine => $engine_settings ) {
				if ( isset( $engine_settings['searchwp_engine_label'] ) ) {
					$engines[ $engine ] = $engine_settings['searchwp_engine_label'];
				}
			}
		}

		// we're going to utilize SearchWP_Live_Search_Form to populate the config dropdown
		$widget_config = isset( $instance['config'] ) ? $instance['config'] : 'default';
		if ( ! class_exists( 'SearchWP_Live_Search_Form' ) ) {
			include_once( dirname( __FILE__ ) . '/class-form.php' );
		}
		$form = new SearchWP_Live_Search_Form();
		$form->setup();
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $widget_title ); ?>">
		</p>
		<?php if ( ! empty( $engines ) ) : ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'engine' ); ?>"><?php _e( 'SearchWP Engine:' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'engine' ); ?>" id="<?php echo $this->get_field_id( 'engine' ); ?>">
				<?php foreach ( $engines as $engine_name => $engine_label ) : ?>
					<option value="<?php echo esc_attr( $engine_name ); ?>" <?php selected( $widget_engine, $engine_name ); ?>><?php echo esc_html( $engine_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php endif; ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'config' ); ?>"><?php _e( 'Configuration:' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'config' ); ?>" id="<?php echo $this->get_field_id( 'config' ); ?>">
				<?php foreach ( $form->configs as $config => $val ) : ?>
					<option value="<?php echo esc_attr( $config ); ?>" <?php selected( $widget_config, $config ); ?>><?php echo esc_html( $config ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php $swpuniqid = uniqid( 'swp' ); ?>
		<p><a href="#" class="button searchwp-widget-<?php echo $swpuniqid; ?>"><?php _e( 'Advanced', 'searchwp' ); ?></a></p>
		<div class="searchwp-live-search-widget-advanced" style="display:none;">
			<p>
				<label for="<?php echo $this->get_field_id( 'placeholder' ); ?>"><?php _e( 'Placholder:' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'placeholder' ); ?>" name="<?php echo $this->get_field_name( 'placeholder' ); ?>" type="placeholder" value="<?php echo esc_attr( $widget_placeholder ); ?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'destination' ); ?>"><?php _e( 'Destination fallback URL (optional):' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'destination' ); ?>" name="<?php echo $this->get_field_name( 'destination' ); ?>" type="text" value="<?php echo esc_attr( $widget_destination ); ?>">
			</p>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('.searchwp-widget-<?php echo $swpuniqid; ?>').click(function(){
					var $advanced = $(this).parents().find('.searchwp-live-search-widget-advanced');
					if($advanced.is(':visible')){
						$advanced.hide();
					}else{
						$advanced.show();
					}
					return false;
				});
			});
		</script>
	<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title']          = ( ! empty( $new_instance['title'] ) )         ? strip_tags( $new_instance['title'] ) : '';
		$instance['destination']    = ( ! empty( $new_instance['destination'] ) )   ? strip_tags( $new_instance['destination'] ) : '';
		$instance['placeholder']    = ( ! empty( $new_instance['placeholder'] ) )   ? strip_tags( $new_instance['placeholder'] ) : '';
		$instance['engine']         = ( ! empty( $new_instance['engine'] ) )        ? strip_tags( $new_instance['engine'] ) : '';
		$instance['config']         = ( ! empty( $new_instance['config'] ) )        ? strip_tags( $new_instance['config'] ) : '';

		return $instance;
	}

}

function searchwp_live_search_register_widget() {
	register_widget( 'SearchWP_Live_Search_Widget' );
}
add_action( 'widgets_init', 'searchwp_live_search_register_widget' );
