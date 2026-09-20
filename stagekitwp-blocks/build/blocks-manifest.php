<?php
// This file is generated. Do not modify it manually.
return array(
	'bookshelf-container' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/bookshelf-container',
		'title' => 'Bookshelf Container',
		'category' => 'stagekitwp-blocks',
		'icon' => 'index-card',
		'description' => 'Display bookshelf items as book spines or cover gallery.',
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'layoutStyle' => array(
				'type' => 'string',
				'default' => 'spines'
			),
			'booksPerShelf' => array(
				'type' => 'number',
				'default' => 4
			),
			'shelfHeight' => array(
				'type' => 'number',
				'default' => 360
			),
			'bookcaseWidth' => array(
				'type' => 'number',
				'default' => 1200
			),
			'shelfGap' => array(
				'type' => 'number',
				'default' => 18
			),
			'shelfTheme' => array(
				'type' => 'string',
				'default' => 'walnut'
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'example' => array(
			'attributes' => array(
				'layoutStyle' => 'spines',
				'booksPerShelf' => 4,
				'shelfHeight' => 360,
				'bookcaseWidth' => 1200,
				'shelfGap' => 18,
				'shelfTheme' => 'walnut'
			),
			'innerBlocks' => array(
				array(
					'name' => 'stagekitwp/bookshelf-item',
					'attributes' => array(
						'bookTitle' => 'The Stage Manager\'s Handbook',
						'authorName' => 'Example Author'
					)
				),
				array(
					'name' => 'stagekitwp/bookshelf-item',
					'attributes' => array(
						'bookTitle' => 'Designing for the Theatre',
						'authorName' => 'Example Author'
					)
				),
				array(
					'name' => 'stagekitwp/bookshelf-item',
					'attributes' => array(
						'bookTitle' => 'A Practical Guide to Production',
						'authorName' => 'Example Author'
					)
				)
			)
		)
	),
	'bookshelf-item' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/bookshelf-item',
		'title' => 'Bookshelf Item',
		'category' => 'stagekitwp-blocks',
		'icon' => 'book-alt',
		'description' => 'Affiliate book card.',
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'rawUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'amazonUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'asin' => array(
				'type' => 'string',
				'default' => ''
			),
			'domain' => array(
				'type' => 'string',
				'default' => 'amazon.com'
			),
			'bookTitle' => array(
				'type' => 'string',
				'default' => ''
			),
			'authorName' => array(
				'type' => 'string',
				'default' => ''
			),
			'description' => array(
				'type' => 'string',
				'default' => ''
			),
			'coverImage' => array(
				'type' => 'string',
				'default' => ''
			),
			'affiliateTag' => array(
				'type' => 'string',
				'default' => ''
			),
			'cardOrientation' => array(
				'type' => 'string',
				'default' => 'horizontal'
			),
			'cardTheme' => array(
				'type' => 'string',
				'default' => 'light'
			),
			'imageAlignment' => array(
				'type' => 'string',
				'default' => 'left'
			),
			'cardSize' => array(
				'type' => 'string',
				'default' => 'normal'
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:../style-index.css',
		'editorStyle' => 'file:../index.css',
		'example' => array(
			'attributes' => array(
				'bookTitle' => 'The Stage Manager\'s Handbook',
				'authorName' => 'Example Author',
				'cardOrientation' => 'horizontal',
				'cardTheme' => 'light',
				'imageAlignment' => 'left',
				'cardSize' => 'normal'
			)
		)
	),
	'stagekitwp-accordion' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/accordion',
		'title' => 'Accordion',
		'category' => 'stagekitwp-blocks',
		'icon' => 'list-view',
		'description' => 'Expandable content panels.',
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'allowMultiple' => array(
				'type' => 'boolean',
				'default' => false
			),
			'openFirst' => array(
				'type' => 'boolean',
				'default' => true
			),
			'stylePreset' => array(
				'type' => 'string',
				'default' => 'minimal'
			),
			'colorMode' => array(
				'type' => 'string',
				'default' => 'auto'
			),
			'headerTextLight' => array(
				'type' => 'string',
				'default' => '#1e293b'
			),
			'headerBackgroundLight' => array(
				'type' => 'string',
				'default' => '#f8fafc'
			),
			'bodyTextLight' => array(
				'type' => 'string',
				'default' => '#334155'
			),
			'bodyBackgroundLight' => array(
				'type' => 'string',
				'default' => '#ffffff'
			),
			'borderLight' => array(
				'type' => 'string',
				'default' => '#cbd5e1'
			),
			'accentLight' => array(
				'type' => 'string',
				'default' => '#b42318'
			),
			'headerTextDark' => array(
				'type' => 'string',
				'default' => '#f8fafc'
			),
			'headerBackgroundDark' => array(
				'type' => 'string',
				'default' => '#1e293b'
			),
			'bodyTextDark' => array(
				'type' => 'string',
				'default' => '#e2e8f0'
			),
			'bodyBackgroundDark' => array(
				'type' => 'string',
				'default' => '#0f172a'
			),
			'borderDark' => array(
				'type' => 'string',
				'default' => '#475569'
			),
			'accentDark' => array(
				'type' => 'string',
				'default' => '#f59e0b'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js'
	),
	'stagekitwp-accordion-item' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/accordion-item',
		'title' => 'Accordion Item',
		'category' => 'stagekitwp-blocks',
		'icon' => 'excerpt-view',
		'description' => 'One expandable accordion panel.',
		'parent' => array(
			'stagekitwp/accordion'
		),
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'title' => array(
				'type' => 'string',
				'default' => 'Accordion item'
			),
			'open' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css'
	),
	'stagekitwp-countdown' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/countdown',
		'title' => 'Countdown',
		'category' => 'stagekitwp-blocks',
		'icon' => 'clock',
		'description' => 'Counts down to a selected date and time.',
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'targetDate' => array(
				'type' => 'string',
				'default' => ''
			),
			'expiredText' => array(
				'type' => 'string',
				'default' => 'The event has started.'
			),
			'showDays' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showHours' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showMinutes' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showSeconds' => array(
				'type' => 'boolean',
				'default' => true
			),
			'alignment' => array(
				'type' => 'string',
				'default' => 'center'
			),
			'colorMode' => array(
				'type' => 'string',
				'default' => 'auto'
			),
			'numberSize' => array(
				'type' => 'number',
				'default' => 48
			),
			'labelSize' => array(
				'type' => 'number',
				'default' => 14
			),
			'numberColor' => array(
				'type' => 'string',
				'default' => ''
			),
			'labelColor' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js'
	),
	'stagekitwp-countup' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/countup',
		'title' => 'Count Up',
		'category' => 'stagekitwp-blocks',
		'icon' => 'chart-line',
		'description' => 'Animated count up number.',
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'preText' => array(
				'type' => 'string',
				'default' => ''
			),
			'startValue' => array(
				'type' => 'number',
				'default' => 0
			),
			'number' => array(
				'type' => 'number',
				'default' => 100
			),
			'postText' => array(
				'type' => 'string',
				'default' => ''
			),
			'colorMode' => array(
				'type' => 'string',
				'default' => 'auto'
			),
			'duration' => array(
				'type' => 'number',
				'default' => 2000
			),
			'delay' => array(
				'type' => 'number',
				'default' => 0
			),
			'animationStyle' => array(
				'type' => 'string',
				'default' => 'ease-out'
			),
			'separator' => array(
				'type' => 'boolean',
				'default' => true
			),
			'alignment' => array(
				'type' => 'string',
				'default' => 'center'
			),
			'numberSize' => array(
				'type' => 'number',
				'default' => 48
			),
			'textSize' => array(
				'type' => 'number',
				'default' => 24
			),
			'marginTop' => array(
				'type' => 'number',
				'default' => 0
			),
			'marginBottom' => array(
				'type' => 'number',
				'default' => 20
			),
			'textColor' => array(
				'type' => 'string',
				'default' => ''
			),
			'numberColor' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js'
	),
	'stagekitwp-divider' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/divider',
		'title' => 'Custom Divider',
		'category' => 'stagekitwp-blocks',
		'icon' => 'minus',
		'description' => 'A themed divider with line, icon, image, and shape presets.',
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'dividerType' => array(
				'type' => 'string',
				'default' => 'line'
			),
			'lineStyle' => array(
				'type' => 'string',
				'default' => 'solid'
			),
			'shape' => array(
				'type' => 'string',
				'default' => 'wave'
			),
			'icon' => array(
				'type' => 'string',
				'default' => '✦'
			),
			'imageUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'imageAlt' => array(
				'type' => 'string',
				'default' => ''
			),
			'color' => array(
				'type' => 'string',
				'default' => ''
			),
			'thickness' => array(
				'type' => 'number',
				'default' => 2
			),
			'width' => array(
				'type' => 'number',
				'default' => 80
			),
			'spacing' => array(
				'type' => 'number',
				'default' => 28
			),
			'colorMode' => array(
				'type' => 'string',
				'default' => 'auto'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css'
	),
	'stagekitwp-dual-image' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/dual-image',
		'title' => 'Dark Mode Image',
		'category' => 'stagekitwp-blocks',
		'icon' => 'format-image',
		'description' => 'Display a light and dark image based on the Theatre Manager theme color mode.',
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'lightImageId' => array(
				'type' => 'number',
				'default' => 0
			),
			'lightImageUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'lightImageAlt' => array(
				'type' => 'string',
				'default' => ''
			),
			'darkImageId' => array(
				'type' => 'number',
				'default' => 0
			),
			'darkImageUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'darkImageAlt' => array(
				'type' => 'string',
				'default' => ''
			),
			'alignment' => array(
				'type' => 'string',
				'default' => 'center'
			),
			'imageWidth' => array(
				'type' => 'number',
				'default' => 100
			),
			'aspectRatio' => array(
				'type' => 'string',
				'default' => 'auto'
			),
			'objectFit' => array(
				'type' => 'string',
				'default' => 'contain'
			),
			'borderRadius' => array(
				'type' => 'number',
				'default' => 0
			),
			'linkUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'linkTargetBlank' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'example' => array(
			'attributes' => array(
				'lightImageUrl' => 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'640\' height=\'360\' viewBox=\'0 0 640 360\'%3E%3Crect width=\'640\' height=\'360\' fill=\'%23f8fafc\'/%3E%3Ccircle cx=\'320\' cy=\'140\' r=\'70\' fill=\'%232563eb\'/%3E%3Cpath d=\'M0 300L180 170L300 260L430 130L640 300V360H0Z\' fill=\'%2364758b\'/%3E%3C/svg%3E',
				'darkImageUrl' => 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'640\' height=\'360\' viewBox=\'0 0 640 360\'%3E%3Crect width=\'640\' height=\'360\' fill=\'%231e293b\'/%3E%3Ccircle cx=\'320\' cy=\'140\' r=\'70\' fill=\'%23fbbf24\'/%3E%3Cpath d=\'M0 300L180 170L300 260L430 130L640 300V360H0Z\' fill=\'%2394a3b8\'/%3E%3C/svg%3E',
				'lightImageAlt' => 'Light mode sample image',
				'darkImageAlt' => 'Dark mode sample image',
				'alignment' => 'center',
				'imageWidth' => 100,
				'aspectRatio' => '16 / 9',
				'objectFit' => 'cover',
				'borderRadius' => 8
			)
		)
	),
	'stagekitwp-lottie' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/lottie',
		'title' => 'Dark Mode Lottie',
		'category' => 'stagekitwp-blocks',
		'icon' => 'format-image',
		'description' => 'Display light and dark Lottie animations with automatic theme switching.',
		'supports' => array(
			'html' => false,
			'align' => array(
				'left',
				'center',
				'right',
				'wide',
				'full'
			)
		),
		'attributes' => array(
			'lightAnimationId' => array(
				'type' => 'number',
				'default' => 0
			),
			'lightAnimationUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'darkAnimationId' => array(
				'type' => 'number',
				'default' => 0
			),
			'darkAnimationUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'colorMode' => array(
				'type' => 'string',
				'default' => 'auto'
			),
			'autoplay' => array(
				'type' => 'boolean',
				'default' => true
			),
			'loop' => array(
				'type' => 'boolean',
				'default' => true
			),
			'speed' => array(
				'type' => 'number',
				'default' => 1
			),
			'width' => array(
				'type' => 'number',
				'default' => 100
			),
			'maxWidth' => array(
				'type' => 'number',
				'default' => 500
			),
			'aspectRatio' => array(
				'type' => 'string',
				'default' => '1 / 1'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScript' => 'file:./view.js'
	),
	'stagekitwp-post-carousel' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/post-carousel',
		'version' => '1.1.0',
		'title' => 'Post Carousel',
		'category' => 'stagekitwp-blocks',
		'icon' => 'slides',
		'description' => 'A responsive post carousel block powered by Swiper.',
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			)
		),
		'textdomain' => 'stagekitwp-blocks',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php',
		'attributes' => array(
			'postsPerPage' => array(
				'type' => 'number',
				'default' => 5
			),
			'category' => array(
				'type' => 'string',
				'default' => ''
			),
			'showExcerpt' => array(
				'type' => 'boolean',
				'default' => true
			),
			'autoplay' => array(
				'type' => 'boolean',
				'default' => false
			),
			'autoplayDelay' => array(
				'type' => 'number',
				'default' => 3000
			),
			'loop' => array(
				'type' => 'boolean',
				'default' => true
			),
			'colorMode' => array(
				'type' => 'string',
				'default' => 'auto'
			)
		),
		'example' => array(
			'attributes' => array(
				'postsPerPage' => 3,
				'showExcerpt' => true,
				'loop' => true,
				'colorMode' => 'auto'
			)
		)
	),
	'stagekitwp-tab-item' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/tab-item',
		'title' => 'Tab Item',
		'category' => 'stagekitwp-blocks',
		'parent' => array(
			'stagekitwp/tabs'
		),
		'attributes' => array(
			'title' => array(
				'type' => 'string',
				'default' => 'New Tab'
			)
		),
		'editorScript' => 'file:./index.js',
		'example' => array(
			'attributes' => array(
				'title' => 'Tab Example'
			),
			'innerBlocks' => array(
				array(
					'name' => 'core/paragraph',
					'attributes' => array(
						'content' => 'Sample tab content.'
					)
				)
			)
		)
	),
	'stagekitwp-tabs' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/tabs',
		'title' => 'StageKit Tabs',
		'category' => 'stagekitwp-blocks',
		'attributes' => array(
			'tabStyle' => array(
				'type' => 'string',
				'default' => 'underline'
			),
			'tabAlignment' => array(
				'type' => 'string',
				'default' => 'flex-start'
			),
			'activeColor' => array(
				'type' => 'string',
				'default' => '#2563eb'
			),
			'activeBgColor' => array(
				'type' => 'string',
				'default' => '#ffffff'
			),
			'folderHeaderBg' => array(
				'type' => 'string',
				'default' => '#f1f5f9'
			),
			'inactiveColor' => array(
				'type' => 'string',
				'default' => '#64748b'
			),
			'inactiveBgColor' => array(
				'type' => 'string',
				'default' => 'transparent'
			),
			'hoverColor' => array(
				'type' => 'string',
				'default' => '#1e293b'
			),
			'hoverBgColor' => array(
				'type' => 'string',
				'default' => '#f1f5f9'
			),
			'tabPadding' => array(
				'type' => 'string',
				'default' => 'medium'
			),
			'borderRadius' => array(
				'type' => 'number',
				'default' => 8
			)
		),
		'editorScript' => 'file:./index.js',
		'viewScript' => 'file:./view.js',
		'style' => 'file:./style-index.css',
		'example' => array(
			'attributes' => array(
				'tabStyle' => 'underline',
				'tabAlignment' => 'flex-start',
				'activeColor' => '#2563eb',
				'activeBgColor' => '#ffffff',
				'inactiveColor' => '#64748b',
				'inactiveBgColor' => '#f1f5f9',
				'tabPadding' => 'medium',
				'borderRadius' => 8
			),
			'innerBlocks' => array(
				array(
					'name' => 'stagekitwp/tab-item',
					'attributes' => array(
						'title' => 'Overview'
					),
					'innerBlocks' => array(
						array(
							'name' => 'core/paragraph',
							'attributes' => array(
								'content' => 'A sample tab panel.'
							)
						)
					)
				),
				array(
					'name' => 'stagekitwp/tab-item',
					'attributes' => array(
						'title' => 'Details'
					),
					'innerBlocks' => array(
						array(
							'name' => 'core/paragraph',
							'attributes' => array(
								'content' => 'Another sample tab panel.'
							)
						)
					)
				)
			)
		)
	),
	'stagekitwp-thermometer' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'stagekitwp/thermometer',
		'title' => 'Fundraising Thermometer',
		'category' => 'stagekitwp-blocks',
		'icon' => 'chart-bar',
		'description' => 'Shows fundraising progress toward a goal.',
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'title' => array(
				'type' => 'string',
				'default' => 'Our fundraising goal'
			),
			'current' => array(
				'type' => 'number',
				'default' => 2500
			),
			'goal' => array(
				'type' => 'number',
				'default' => 10000
			),
			'currency' => array(
				'type' => 'string',
				'default' => '$'
			),
			'theme' => array(
				'type' => 'string',
				'default' => 'horizontal'
			),
			'showAmounts' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showPercentage' => array(
				'type' => 'boolean',
				'default' => true
			),
			'colorMode' => array(
				'type' => 'string',
				'default' => 'auto'
			),
			'accentColor' => array(
				'type' => 'string',
				'default' => '#c2410c'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css'
	)
);
