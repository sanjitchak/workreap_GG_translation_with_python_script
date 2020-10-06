.po files are LOCOTRANSLATE plugin files.

JSON file are SNIPPETS plugin file, to add custom woocommerce code


Some custom code added in digitalmarket.com workreap theme:-

1. in "wp-content/themes/workreap/directory/front-end/hooks.php" :-

AFTER :-
if( !function_exists(  'workreap_price_format' ) ) {
	function workreap_price_format($price='', $type = 'echo'){

ADDED THIS LINE :-
//add by Sanjit to get currency converted price
if (function_exists( 'wmc_get_price')) {
	$price = wmc_get_price($price,false,false);
	}

2. in wp-content/themes/workreap/includes/class-headers.php:-

ADD this:-

	$defult_key		= 'services';

under function workreap_prepare_search_form() and workreap_prepare_search_formv3().

3. in wp-content/plugins/workreap_core/elementor/shortcodes/wt-banner-v2.php :-

ADD this:-

	$defult_key		= 'services';

To change default search category.

4. Fixed inconsistent update by switching off cloudways VARNISH and cache.
