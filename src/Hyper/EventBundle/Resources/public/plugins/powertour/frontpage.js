$(document).ready(function($){

	$.powerTour({
		tours:[
				{
					trigger            : '#starttour',
					startWith          : 1,
					easyCancel         : true,
					escKeyCancel       : true,
					scrollHorizontal   : false,
					keyboardNavigation : true,
					loopTour           : false,
					highlightStartSpeed: 200,//new 2.5.0
					highlightEndSpeed  : 200,//new 2.3.0
					onStartTour        : function(ui){
						// show bottom bar
						$('#demo-bar-footer').animate({bottom: 0},1000);

					},
					onEndTour          : function(ui){
						if (ui.endType == 'cancel') {
							UserTutorial.hideEndTour();
						} else {
							if (UserTutorial.showTutorial) {
								UserTutorial.hideEndTour();
							} else {
								UserTutorial.endTour();
							}
						}

					},
					onProgress         : function(ui){

						var i       = ui.stepIndex;
						var total   = ui.totalSteps;
						var barSize = 100 / total * i+'%';

						// progress meter
						$('#progressmeter-text').html('<span>'+i+'</span> / '+total+'').prev('#progressmeter-bar').animate({width: barSize},400);

					},
					steps:[
							{
								hookTo          : '',//not needed
								content         : '#step-1',
								width           : 440,
								position        : 'sc',
								offsetY         : 0,
								offsetX         : 0,
								fxIn            : 'fadeIn',
								fxOut           : 'bounceOutUp',
								showStepDelay   : 500,
								center          : 'step',
								scrollSpeed     : 0,
								scrollEasing    : 'swing',
								scrollDelay     : 0,
								timer           : '00:00',
								highlight       : true,
								keepHighlighted : true,
								keepVisible     : false,// new 2.2.0
								onShowStep      : function(ui){ },
								onHideStep      : function(ui){ }
							},
							{
								hookTo          : '#tour-highlight',//not needed
								content         : '#step-2',
								width           : 440,
								position        : 'rt',
								offsetY         : 0,
								offsetX         : 10,
								fxIn            : 'fadeIn',
								fxOut           : 'bounceOutLeft',
								showStepDelay   : 1000,
								center          : 'step',
								scrollSpeed     : 0,
								scrollEasing    : 'swing',
								scrollDelay     : 0,
								timer           : '00:00',
								highlight       : true,
								keepHighlighted : true,
								highlightElements : '',
								keepVisible     : false,// new 2.2.0
								onShowStep      : function(ui){
									$('#mainmenu-dashboard').addClass('active');
								},
								onHideStep      : function(ui){
									$('#mainmenu-dashboard').removeClass('active');
								}
							},
							{
								hookTo          : '#tour-highlight',//not needed
								content         : '#step-3',
								width           : 440,
								position        : 'rt',
								offsetY         : 0,
								offsetX         : 10,
								fxIn            : 'fadeIn',
								fxOut           : 'bounceOutLeft',
								showStepDelay   : 1000,
								center          : 'step',
								scrollSpeed     : 0,
								scrollEasing    : 'swing',
								scrollDelay     : 0,
								timer           : '00:00',
								highlight       : true,
								keepHighlighted : true,
								highlightElements : '',
								keepVisible     : false,// new 2.2.0
								onShowStep      : function(ui){
									$('#mainmenu-cardbuilder').addClass('active');
									$('#mainmenu-dashboard').removeClass('active');
								},
								onHideStep      : function(ui){
									$('#mainmenu-cardbuilder').removeClass('active');
									$('#mainmenu-dashboard').addClass('active');
								}
							},
							{
								hookTo          : '#tour-highlight',//not needed
								content         : '#step-4',
								width           : 440,
								position        : 'rt',
								offsetY         : 0,
								offsetX         : 10,
								fxIn            : 'fadeIn',
								fxOut           : 'bounceOutLeft',
								showStepDelay   : 1000,
								center          : 'step',
								scrollSpeed     : 0,
								scrollEasing    : 'swing',
								scrollDelay     : 0,
								timer           : '00:00',
								highlight       : true,
								keepHighlighted : true,
								highlightElements : '',
								keepVisible     : false,// new 2.2.0
								onShowStep      : function(ui){
									$('#mainmenu-audiencedeck').addClass('active');
									$('#mainmenu-dashboard').removeClass('active');
								},
								onHideStep      : function(ui){
									$('#mainmenu-audiencedeck').removeClass('active');
									$('#mainmenu-dashboard').addClass('active');
								}
							},
							{
								hookTo          : '#tour-highlight',//not neededmain
								content         : '#step-5',
								width           : 440,
								position        : 'rt',
								offsetY         : 0,
								offsetX         : 10,
								fxIn            : 'fadeIn',
								fxOut           : 'bounceOutLeft',
								showStepDelay   : 1000,
								center          : 'step',
								scrollSpeed     : 0,
								scrollEasing    : 'swing',
								scrollDelay     : 0,
								timer           : '00:00',
								highlight       : true,
								keepHighlighted : true,
								highlightElements : '',
								keepVisible     : false,// new 2.2.0
								onShowStep      : function(ui){
									$('#mainmenu-userjourney').addClass('active');
									$('#mainmenu-dashboard').removeClass('active');
								},
								onHideStep      : function(ui){
									$('#mainmenu-userjourney').removeClass('active');
									$('#mainmenu-dashboard').addClass('active');
								}
							}
					],
					stepDefaults:[
							{
								width           : 300,
								position        : 'tr',
								offsetY         : 0,
								offsetX         : 0,
								fxIn            : 'fadeIn',
								fxOut           : 'fadeOut',
								showStepDelay   : 0,
								center          : 'step',
								scrollSpeed     : 400,
								scrollEasing    : 'swing',
								scrollDelay     : 0,
								timer           : '00:00',
								highlight       : true,
								keepHighlighted : false,
								keepVisible     : false,// new 2.2.0
								onShowStep      : function(ui){ },
								onHideStep      : function(ui){ }
							}
					]
				}
			]
	});
	UserTutorial.init();

});