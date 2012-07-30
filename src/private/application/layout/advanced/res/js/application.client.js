;
Application.Client = function() {
	
	var deviceOs;
	
	return {
		
		init: function() {
			var ua = navigator.userAgent;
			deviceOs = {
				iphone: ua.match(/(iPhone|iPod|iPad)/),
				ipad: ua.match(/iPad/),
				blackberry: ua.match(/BlackBerry/),
				android: ua.match(/Android/)
			};
		},
		
		deviceOs: function() {
			if (deviceOs.ipad) {
				return 'ipad';
			} else if (deviceOs.iphone) {
				return 'iphone';
			} else if (deviceOs.blackberry) {
				return 'blackberry';
			} else {
				return 'android';
			};
		},
		
		isMobile: function() {
			return deviceOs.iphone || deviceOs.blackberry || deviceOs.android;
		}
		
	}
	
}();

Application.addBootstrap(Application.Client.init);