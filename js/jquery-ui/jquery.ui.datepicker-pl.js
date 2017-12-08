/* Polish initialisation for the jQuery UI date picker plugin. */
/* Written by Jacek Wysocki (jacek.wysocki@gmail.com). */
(function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define([ "../datepicker" ], factory );
	} else {

		// Browser globals
		factory( jQuery.datepicker );
	}
}(function( datepicker ) {

datepicker.regional['pl'] = {
	closeText: 'Zamknij',
	prevText: 'Poprzedni',
	nextText: 'Następny',
	currentText: 'Dzień',
	monthNames: ['Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec',
	'Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'],
	monthNamesShort: ['Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec',
	'Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'],
	dayNames: ['Niedziela','Poniedziałek','Wtorek','Środa','Czwartek','Piątek','Sobota'],
	dayNamesShort: ['Ndz','Pn','Wt','Śr','Czw','Pt','So'],
	dayNamesMin: ['Nd','Pn','Wt','Śr','Cz','Pt','So'],
	weekHeader: 'Tydz',
	dateFormat: 'dd/mm/yy',
	firstDay: 1,
	isRTL: false,
	showMonthAfterYear: false,
	yearSuffix: ''};
datepicker.setDefaults(datepicker.regional['pl']);

return datepicker.regional['pl'];

}));