import $ from 'jquery';

const initSlickCarousels = () => {
	$( '.stagekit-slick-slider' ).each( function () {
		const $slider = $( this );

		if ( $slider.hasClass( 'slick-initialized' ) ) {
			return;
		}

		const isAutoplay = $slider.data( 'autoplay' ) === true || $slider.data( 'autoplay' ) === 'true';
		const autoplaySpeed = parseInt( $slider.data( 'autoplay-speed' ), 10 ) || 3000;
		const isInfinite = $slider.data( 'infinite' ) === true || $slider.data( 'infinite' ) === 'true';

		$slider.slick( {
			slidesToShow: 3,
			slidesToScroll: 1,
			infinite: isInfinite,
			autoplay: isAutoplay,
			autoplaySpeed: autoplaySpeed,
			dots: true,
			arrows: true,
			prevArrow: '<button type="button" class="slick-prev">&lsaquo;</button>',
			nextArrow: '<button type="button" class="slick-next">&rsaquo;</button>',
			responsive: [
				{
					breakpoint: 1024,
					settings: {
						slidesToShow: 2,
						slidesToScroll: 1,
					},
				},
				{
					breakpoint: 640,
					settings: {
						slidesToShow: 1,
						slidesToScroll: 1,
					},
				},
			],
		} );
	} );
};

$( document ).ready( initSlickCarousels );