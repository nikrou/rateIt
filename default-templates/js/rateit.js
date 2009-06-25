$(function(){if(!document.getElementById){return;}var rateit=new rateIt();rateit.init();});function rateIt(){};

rateIt.prototype={

	blocs:new Array(),
	service_url:'services.php',
	msg_call_server_error:'Failed to call server',
	msg_thanks:'Thank you for having voted',

	init:function(){
		$.fn.rating.options.required = true;

		var This=this;
		for(i=0;i<this.blocs.length;i++){
			$('.'+this.blocs[i]).find('.rateit-linker').each(function(){
				var def = $(this).attr('id').split('-');
				if (def[2] == undefined || def[3] == undefined) return;
				var type=def[2];
				var id=def[3];

				$(function(){
					$('input.rateit-'+type+'-'+id).rating({
						callback:function(note,link){
							$('input.rateit-'+type+'-'+id).rating('disable');
							$.ajax({
								timeout:3000,
								url:This.service_url,
								type:'POST',
								data:{f:'rateItVote',voteType:type,voteId:id,voteNote:note},
								error:function(){alert(This.msg_call_server_error);},
								success:function(data){
									data=$(data);
									if(data.find('rsp').attr('status')=='ok'){
										for(i=0;i<This.blocs.length;i++){
											$('#'+This.blocs[i]+'-total-'+type+'-'+id).text(data.find('item').attr('total'));
											$('#'+This.blocs[i]+'-max-'+type+'-'+id).text(data.find('item').attr('max'));
											$('#'+This.blocs[i]+'-min-'+type+'-'+id).text(data.find('item').attr('min'));
											$('#'+This.blocs[i]+'-note-'+type+'-'+id).text(data.find('item').attr('note'));
											$('#'+This.blocs[i]+'-quotient-'+type+'-'+id).text(data.find('item').attr('quotient'));
											$('#'+This.blocs[i]+'-fullnote-'+type+'-'+id).text(data.find('item').attr('note')+'/'+data.find('item').attr('quotient'));
										}
										alert(This.msg_thanks);
									}else{
										alert($(data).find('message').text());
									}
								}
							});
						}
					});
				});
				$(this).children('p').children('input:submit').hide();
				$(this).children('p').after('<p>&nbsp;</p>');
			});
		}
	}
};