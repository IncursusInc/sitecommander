		// Set up broadcasting framework
		var pusher = new Pusher(drupalSettings.sitecommander.pusherAppKey, { cluster: "ap1", authEndpoint: '/sitecommander/pusherAuth' });
		var scChannel = pusher.subscribe('site-commander');
		//var scPresenceChannel = pusher.subscribe('presence-site-commander');

		pusher.bind('broadcastMessage', function(data) {

			toastr.options = {
				"closeButton": true,
				"debug": false,
				"newestOnTop": false,
				"progressBar": false,
				"positionClass": data.messagePosition,
				"preventDuplicates": false,
				"onclick": null,
				"showDuration": "300",
				"hideDuration": "1000",
				"timeOut": 0,
				"extendedTimeOut": 0,
				"showEasing": "swing",
				"hideEasing": "linear",
				"showMethod": "fadeIn",
				"hideMethod": "fadeOut",
				"tapToDismiss": false
			}
		
			switch(data.messageType) 
			{
				case 'info': toastr.info(data.messageBody); break;
				case 'warning': toastr.warning(data.messageBody); break;
				case 'error': toastr.error(data.messageBody); break;
				case 'success': toastr.success(data.messageBody); break;
			}
		});
