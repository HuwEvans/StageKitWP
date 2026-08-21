<?php

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Taxonomy {

	public static function init() {
	    add_action('init', [__CLASS__, 'register_taxonomy']);
	    add_action('init', [__CLASS__, 'add_default_terms']);
	}

	public static function register_taxonomy() {
	
	    register_taxonomy(
	        'stagekitwp_interest',
	        'user',
	        [
	            'label' => 'Interests',
	            'public' => false,
	            'hierarchical' => false,
	        ]
	    );
	
	    // ✅ CRITICAL for user taxonomy support
	    register_taxonomy_for_object_type('stagekitwp_interest', 'user');
	}
	
	public static function add_default_terms() {
	    $terms = [
	        'Acting',
	        'Directing',
	        'Stage Management',
	        'Lighting',
	        'Sound',
	        'Set Design',
	        'Costumes',
	        'Props',
	        'Set Building',
	        'Set Dressing',
	        'Painting',
	        'Graphic Design'
	    ];
	
	    foreach ($terms as $term) {
	        if (!term_exists($term, 'stagekitwp_interest')) {
	            wp_insert_term($term, 'stagekitwp_interest');
	        }
	    }
	}
}