(function ($, Drupal) {
	window.basket_ajax_link = function(obj, ajax_url, post){
		if(!post){
			post = $(obj).data('post');
		}
		if(post && post.post_type){
			switch(post.post_type){
				case'change_currency':
					post['set_currency'] = $(obj).val();
				break;
				case'add':
					post['count'] = $(obj).parents('.basket_add_button_wrap:first').find('.count_input').val();
					if(post.add_key){
						var setParams = $('[data-params_key="'+post.add_key+'"]').data('set_params');
						if(setParams){
							post['params'] = setParams;
						}
					}
				break;
			}
		}
		if(post && post.set_val){
			post.set_val = $(obj).val();
		}
		Drupal.ajax({
			url: ajax_url,
			submit: post,
			element: obj,
			progress: {type: 'fullscreen'}
		}).execute();
	}
	window.basket_input_count_format = function(obj){
    var count = parseFloat( $(obj).val() );
    var cntSymbol = 0;
    if ( $(obj).attr('data-basket-scale') ){
      cntSymbol = $(obj).attr('data-basket-scale');
    }

    var cm = Math.ceil(count/$(obj).attr('step'));
    count = cm*$(obj).attr('step');

    count = count.toFixed(cntSymbol);
    $(obj).val(count);
  }

	window.basket_change_input_count = function(obj, type, ajax_url){
		var wrap  = $(obj).parents('.basket_add_button_wrap:first');
		var count = parseFloat( wrap.find('.count_input').val() );
    var step  = parseFloat( wrap.find('.count_input').attr('step') );
    var cntSymbol = 0;
    if ( wrap.find('.count_input').attr('data-basket-scale') ){
      cntSymbol = wrap.find('.count_input').attr('data-basket-scale');
    }
		var ajax_send = true;
		switch(type){
			case'+':
				count = count + step;
			break;
			case'-':
				count = count - step;
			break;
		}
		count = count.toFixed(cntSymbol);
		if(count < wrap.find('.count_input').attr('min')){
			count = wrap.find('.count_input').attr('min');
			if(type != '') ajax_send = false;
		}
		wrap.find('.count_input').val(count);

		$(document).trigger('basketChangeInputCount', [$(obj), count]);

		if(ajax_url && ajax_send){
			var post = $(obj).data('post');
			post['count'] = count;
			basket_ajax_link(obj, ajax_url, post);
		}
	}

	window.basket_orders_toggle_info = function(obj, key_row){
		$(obj).html($(obj).hasClass('active') ? '+' : '-').toggleClass('active');
		$(obj).parents('tr:first').toggleClass('active_tr');
		$('tr.order_line_info_'+key_row).toggle().toggleClass('open');
	}

	Drupal.AjaxCommands.prototype.basketReplaceWith = function(ajax, response) {
		var $wrapper = response.selector ? $(response.selector) : $(ajax.wrapper);
		var $newContent = $($.parseHTML(response.data, document, true));
		$newContent = Drupal.theme('ajaxWrapperNewContent', $newContent, ajax, response);
		$wrapper['replaceWith']($newContent);
	}

  $.fn.BasketReattachBehaviors = function() {
    Drupal.attachBehaviors();
  }

	if (!$.fn.NotyGenerate) {
		$.fn.NotyGenerate = function(type, text) {
			if(type == 'basket_status') {
				type = 'status';
			}
			$('.messages').remove();
			$('#main').prepend('<div class="messages messages--'+type+'">'+text+'</div>');
		}
	}
})(jQuery, Drupal);