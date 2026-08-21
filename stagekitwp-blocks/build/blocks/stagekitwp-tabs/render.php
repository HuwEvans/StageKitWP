<?php
/**
 * Dynamic Tab Block Render Template
 */

// Example tab data structure (adjust attribute names to match your block.json/attributes)
$tabs = isset( $attributes['tabs'] ) ? $attributes['tabs'] : array(
	array( 'title' => 'Tab 1', 'content' => 'Content for tab 1' ),
	array( 'title' => 'Tab 2', 'content' => 'Content for tab 2' ),
	array( 'title' => 'Tab 3', 'content' => 'Content for tab 3' ),
);

$block_id = 'stagekit-tabs-' . uniqid();
?>

<div <?php echo get_block_wrapper_attributes( array( 'class' => 'stagekit-tabs-wrapper', 'id' => $block_id ) ); ?>>
	<!-- Tab Navigation -->
	<div class="stagekit-tabs-nav" role="tablist">
		<?php foreach ( $tabs as $index => $tab ) : 
			$tab_id   = $block_id . '-tab-' . $index;
			$panel_id = $block_id . '-panel-' . $index;
			$is_active = ( 0 === $index );
		?>
			<button 
				type="button" 
				class="stagekit-tab-btn <?php echo $is_active ? 'active' : ''; ?>" 
				role="tab" 
				id="<?php echo esc_attr( $tab_id ); ?>" 
				aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>" 
				aria-controls="<?php echo esc_attr( $panel_id ); ?>"
				tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
			>
				<?php echo esc_html( $tab['title'] ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<!-- Tab Panels -->
	<div class="stagekit-tabs-panels">
		<?php foreach ( $tabs as $index => $tab ) : 
			$tab_id   = $block_id . '-tab-' . $index;
			$panel_id = $block_id . '-panel-' . $index;
			$is_active = ( 0 === $index );
		?>
			<div 
				class="stagekit-tab-panel <?php echo $is_active ? 'active' : ''; ?>" 
				id="<?php echo esc_attr( $panel_id ); ?>" 
				role="tabpanel" 
				aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
				<?php echo ! $is_active ? 'hidden' : ''; ?>
			>
				<?php echo wp_kses_post( $tab['content'] ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>