(function ($, Drupal) {
	Drupal.behaviors.BasketAdmin = {
		attach: function (context, settings){
			$('input.color_input').each(function(){
				var obj = $(this);
				$(obj).css('backgroundColor', $(obj).val());
				$(obj).ColorPicker({
					color: obj.val(),
					onChange: function (hsb, hex, rgb) {
						$(obj).css('backgroundColor', '#'+hex);
						$(obj).val('#'+hex);
					}
				});
			});
			$('.tooltipster_init').once('tooltipster').each(function(){
                var obj = $(this);
                obj.tooltipster({
	                content: obj.parent().find('.tooltipster_content').html(),
	                contentAsHTML: true,
	                animation: 'grow',
	                delay: 200,
	                theme: 'tooltipster-shadow',
	                trigger: 'click',
	                position: obj.data('position') ? obj.data('position') : 'left',
	                interactive: true,
	                zIndex: 777
	            });
            });
            $('.info-help').once('help').each(function(){
                var obj = $(this);
                obj.tooltipster({
	                content: obj.parent().find('.info-help-content').html(),
	                contentAsHTML: true,
	                animation: 'grow',
	                delay: 200,
	                theme: 'tooltipster-shadow',
	                trigger: 'click',
	                interactive: true,
	                maxWidth: 250,
	                zIndex: 777
	            });
            });
            $('select[multiple="multiple"]').each(function(){
            	var texts = $(this).data('texts');
            	if(texts){
	            	$(this).multipleSelect({
				        selectAllText: texts.selectAllText,
				        allSelected: texts.allSelected,
				        countSelected: texts.countSelected,
				        noMatchesFound: texts.noMatchesFound,
				        width: '100%',
				        selectAll: false
					});
            	} else {
            		$(this).multipleSelect({
				        width: '100%',
				        selectAll: false,
				        maxHeight: 150
					});
            	}
            });
            $('.inline_twig').once('inline_twig').each(function(){
            	var obj = $(this);
            	CodeMirror.defineMode("htmltwig", function(config, parserConfig) {
				  return CodeMirror.overlayMode(CodeMirror.getMode(config, 'htmlmixed'), CodeMirror.getMode(config, "twig"));
				});
            	var editor = CodeMirror.fromTextArea(document.getElementById(obj.attr('id')), {
				    lineNumbers: true,
				    styleActiveLine: true,
				    matchBrackets: true,
				    theme: 'bespin',
				    mode: obj.hasClass('php') ? 'application/x-httpd-php' : 'htmltwig',
				    matchTags: {bothTags: true},
				    extraKeys: {"Ctrl-J": "toMatchingTag"},
				    viewportMargin: Infinity,
				});
				editor.on("change", function() {
				   obj.val(editor.getValue());
				});
            });
            $('.table_excel_wrap').once('table_excel_wrap').each(function(){
            	$(this).on('scroll', function () {
					$('.table_excel_wrap').scrollLeft($(this).scrollLeft());
				});
            });
		}
	}
	window.page_resize = function(){
        if ( $('#basket_admin_page').length == 0 ){
            return;
        }
		$('#basket_admin_page .basket_toolbar_menu').css({
			'margin-top': $('#basket_admin_page').position().top
		});
	}
	page_resize();
	$(window).resize(function(){
		page_resize();
	});
	window.basket_admin_ajax_link = function(obj, ajax_url){
		var post = $(obj).data('post');
		if(post && post.set_val){
			post.set_val = $(obj).val();
		}
		if(post && post.paramsKey){
			post.set_params = $('[data-params_key="'+post.paramsKey+'"]').data('set_params');
		}

		Drupal.ajax({
			url: ajax_url,
			submit: post,
			element: obj,
			progress: {type: 'throbber'}
		}).execute();
	}
	window.basket_set_combo_status = function(obj){
		$('input[name="combo_status"]').val($(obj).attr('data-post')).parents('form:first').find('.form-submit').trigger('click');
	}
	window.basket_order_edit_load_tab = function(obj, ajax_url){
		var post = $(obj).data('post');
		$('.order_tabs a.is-active').removeClass('is-active');
		$(obj).addClass('is-active');
		$('#basket_order_edit_tab_content .tab_content').hide();
		if(!$('#basket_order_edit_tab_content .tab_content[data-tab-content="'+post.tab+'"]').length){
			basket_admin_ajax_link(obj, ajax_url);
		} else {
			$('#basket_order_edit_tab_content .tab_content[data-tab-content="'+post.tab+'"]').show();
		}
	}
	setInterval(function(){
		page_resize();
	}, 500);
	$.fn.BasketOpenNewWindow = function(url, width, height){
		if(!height) height = 400;
		if(!width) width = 960;
		if(height == '100%') height = $(window).height();
		if(width == '100%') width = $(window).width();
		window.open(url, '_blank', 'menubar=no,location=no,height='+height+',width='+width+',scrollbars=no,status=no');
		return false;
	};
	window.basket_admin_checked_all = function(obj, field_name){
		$('input[name^="'+field_name+'"]').prop('checked', $(obj).prop('checked'));
	}
	window.basket_operations_ajax = function(obj, ajax_url){
		var post = $(obj).data('post');
		post['operatinIds'] = [];
		$('input[name^="'+post.name+'"]').each(function(){
			if($(this).prop('checked')){
				var nid = $(this).val();
				post['operatinIds'].push(nid);
			}
		});
		if(drupalSettings.pageFilter) {
			$('input[name="page"]').val(drupalSettings.pageFilter);
		}
		Drupal.ajax({
			url: ajax_url,
			submit: post,
			element: obj,
			progress: {type: 'throbber'}
		}).execute();
	}
	if (!$.fn.NotyGenerate) {
		$.fn.NotyGenerate = function(type, text) {
			$('.messages').remove();
			$('.add_block_list').after('<div role="contentinfo" class="messages messages--'+type+'">'+text+'</div>');
		}
	}
})(jQuery, Drupal);