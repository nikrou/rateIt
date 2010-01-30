/* -- BEGIN LICENSE BLOCK ----------------------------------
 * This file is part of rateIt, a plugin for Dotclear 2.
 * 
 * Copyright (c) 2009-2010 JC Denis and contributors
 * jcdenis@gdwd.com
 * 
 * Licensed under the GPL version 2.0 license.
 * A copy of this license is available in LICENSE file or at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -- END LICENSE BLOCK ------------------------------------*/

;if(window.jQuery) (function($) {

	$.fn.rateit = function(options) {

		var opts = $.extend({}, $.fn.rateit.defaults, options);

		return this.each(function() {
			parseRateit(this,opts.service_url,opts.service_func,opts.blog_uid,opts.enable_cookie,opts.image_size,opts.msg_thanks);
		});
	};

	function parseRateit(target,service_url,service_func,blog_uid,enable_cookie,image_size,msg_thanks) {

		$.fn.rating.options.required = true;
		$.fn.rating.options.starWidth = image_size;
		
		var bloc = target;

		$(target).find('.rateit-linker').each(function(){

			var def = $(this).attr('id').split('-');

			if (def[2] == undefined || def[3] == undefined) return;

			var type=def[2];
			var id=def[3];

			if (enable_cookie==1)
				var oldvote = $.cookie('rateit-'+type+'-'+id);
			else
				var oldvote = 0;

			if (oldvote==1)
				$('input.rateit-'+type+'-'+id).rating({readOnly:true});
			else {
				$(function(){
					$('input.rateit-'+type+'-'+id).rating({
						callback:function(note,link){
							$('input.rateit-'+type+'-'+id).rating('disable');
							$.ajax({
								timeout:3000,
								url:service_url,
								type:'POST',
								data:{f:service_func,voteType:type,voteId:id,voteNote:note},
								error:function(){alert('Failed to call server');},
								success:function(data){
									data=$(data);
									if(data.find('rsp').attr('status')=='ok'){

										$('*').find('#rateit-total-'+type+'-'+id).each(function(){$(this).text(data.find('item').attr('total'))});
										$('*').find('#rateit-max-'+type+'-'+id).each(function(){$(this).text(data.find('item').attr('max'))});
										$('*').find('#rateit-min-'+type+'-'+id).each(function(){$(this).text(data.find('item').attr('min'))});
										$('*').find('#rateit-note-'+type+'-'+id).each(function(){$(this).text(data.find('item').attr('note'))});
										$('*').find('#rateit-quotient-'+type+'-'+id).each(function(){$(this).text(data.find('item').attr('quotient'))});
										$('*').find('#rateit-fullnote-'+type+'-'+id).each(function(){$(this).text(data.find('item').attr('note')+'/'+data.find('item').attr('quotient'))});

										if (msg_thanks!='')
											$('*').find('#rateit-linker-'+type+'-'+id).each(function(){$(this).empty().append('<p>'+msg_thanks+'</p>')});

										if (enable_cookie==1) {
											$.cookie('rateit-'+type+'-'+id,null);
											$.cookie('rateit-'+type+'-'+id,1,{ expires: 365});
										}
									}else{
										alert($(data).find('message').text());
									}
								}
							});
						}
					});
				});
			}
			$(this).children('p').children('input:submit').hide();
			$(this).children('p').after('<p>&nbsp;</p>');
		});
		return target;
	}

	$.fn.rateit.defaults = {
		service_url: '',
		service_func: 'rateItVote',
		blog_uid: '',
		enable_cookie: 0,
		image_size: 16,
		msg_thanks: 'Thank you for having voted'
	};

})(jQuery);
