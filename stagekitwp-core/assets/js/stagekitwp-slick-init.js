jQuery(document).ready(function($) {
  $('.stagekitwp-advertiser-slider').slick({
    dots: true,
    arrows: true,
    autoplay: true,
    autoplaySpeed: 3000,
    slidesToShow: 1,
    slidesToScroll: 1,
    adaptiveHeight: true,
    responsive: [
      {
        breakpoint: 768,
        settings: {
          arrows: false,
          dots: true
        }
      }
    ]
  });
});


jQuery(document).ready(function($) {
    $('.stagekitwp-sponsor-slide').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
		adaptiveHeight: true,
        autoplay: true,
        autoplaySpeed: 3000,
        arrows: true,
        dots: true,
        responsive: [
            { breakpoint: 1024, settings: { slidesToShow: 1 } },
            { breakpoint: 600, settings: { slidesToShow: 1 } }
        ]
    });
});


