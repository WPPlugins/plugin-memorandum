jQuery( document ).ready( function($) {
	$( '.memo-switch a' ).click( function() {
		var getParentObj = $(this).parent().parent();
		var getParentId = $(this).parent().parent().attr('id');
		var setOpenTextarea = $('+ .plugin_memorandum_text',getParentObj);
		var toggleSwitch = $('.plugin_memorandum_switch',getParentObj);
		var cookieName = 'pluginMemo[' + getParentId + ']';
		var textareaVisible = $(setOpenTextarea).is(':visible');
		if ( $(setOpenTextarea).is(':visible') ) {
			$(toggleSwitch).attr('src', $(toggleSwitch).attr('src').replace( '-close.png', '-open.png' ));
			$.cookies.del( cookieName );
		} else {
			$(toggleSwitch).attr('src', $(toggleSwitch).attr('src').replace( '-open.png', '-close.png' ));
			$.cookies.set( cookieName, 1, {expires:7} );

		}
		$(setOpenTextarea).not(':animated').fadeToggle();
		return false;
	});
});