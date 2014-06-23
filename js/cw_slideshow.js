jQuery(document).ready(function($){
	if ( typeof cw_nivo_slider_args !== 'undefined' ) {
		$('.cw-slider').nivoSlider( cw_nivo_slider_args );
	} else {
		$('.cw-slider').nivoSlider();
	}
});