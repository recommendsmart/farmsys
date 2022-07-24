(function ($, Drupal) {
	var time = 4;
	var i = 1;
	var it = 4;
	var interval = setInterval(function() {
		$('#basket_payment_form .seconds').text(it);
		if (i == time) {
			document.getElementById('basket_payment_form').submit();
			clearInterval(interval);
			return;
  		}
  		i++;
  		it--;
	}, 1000);
})(jQuery, Drupal);