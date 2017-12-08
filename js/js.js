$(document).ready(function(){

	// show reminder box content if user marks it
	$("#reminder_acceptance").change(function (e) {
		if ($(this).is(':checked')) { //If the checkobx is checked
			$('#box-reminder').addClass('expanded');
		} else {
			$('#box-reminder').removeClass('expanded');
		}
	});
	if ($("#reminder_acceptance").is(':checked')) { //If the checkobx is checked
		$('#box-reminder').addClass('expanded');
	} else {
		$('#box-reminder').removeClass('expanded');
	}
	
	// hide terms acceptance error message if user marks it
	$("#terms_acceptance").change(function (e) {
		if ($(this).is(':checked')) { //If the checkobx is checked
			$("#terms_acceptance_error").addClass('hide');
		} else {
		}
	});
	
	
	// hide validation errors on focus
	$(document).on('click', '.validation-error input, .validation-error textarea', function() {
		$(this).parents('div.validation-error').removeClass('validation-error');
	});
	
	
	//datepicker
	$(function() {
		$( "#oc_end_date" ).datepicker({ 
		minDate: 0,
		changeMonth: true,
		changeYear: true,
		onSelect: function(dateText, inst) {
			$("#oc_end_date_error").addClass('hide');
		}
		});
	});
	
	
	//input type file
	inputFile();
	

	//-- validate form
	function validateEmail(email) { 
		var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		return re.test(email);
	}
	
	
	//-- on submit
	$('#help-form').on('submit', function(e) {
	
		$('.btn-loader').addClass('loading');
		
		$('.validation-error').removeClass('validation-error');
		$('div.checkbox').removeClass('error');
		$("#terms_acceptance_error").addClass('hide');
		var can = true; //sentinel
		
		//message must have at least 3 words
		if ( $('#message').val().split(/\s+/).length < 3) { 
			$('#help-form #message').parent('div').parent('div').addClass('validation-error'); 
			$("#message_error").removeClass('hide');
			e.preventDefault();
			isValidMsgOnTyping(); // watch msg textarea, hide validation error notification if user writes at least 3 words
			setTimeout(function() {
			   $('.btn-loader').removeClass('loading');
			}, 500);
		}
		
		//email
		if ( !validateEmail($('#email').val()) ) { 
			$('#help-form #email').parent('div.field').addClass('validation-error'); 
			e.preventDefault();
			setTimeout(function() {
			   $('.btn-loader').removeClass('loading');
			}, 500);
		}
		
		//terms acceptance
		if (!$("#terms_acceptance").is(':checked')) {
			$("#terms_acceptance").parent('label').parent('div.checkbox').addClass('error');
			$("#terms_acceptance_error").removeClass('hide');
			e.preventDefault();
			setTimeout(function() {
			   $('.btn-loader').removeClass('loading');
			}, 500);
		}
		
		//reminder requires date
		if ($("#reminder_acceptance").is(':checked') && $('#oc_end_date').val() == "") { // If the reminder is checked but date is missing
			$("#oc_end_date").parent('div').addClass('validation-error');
			$("#oc_end_date_error").removeClass('hide');
			e.preventDefault();
			setTimeout(function() {
			   $('.btn-loader').removeClass('loading');
			}, 500);
		}
		
		if ($("#reminder_acceptance").is(':checked') && isValidDate("oc_end_date", "dd/mm/yy") === false ) { // If the reminder is checked but date is incorret
			$("#oc_end_date").parent('div').addClass('validation-error');
			$("#oc_end_date_error").removeClass('hide');
			e.preventDefault();
			isValidDateOnTyping(); // watch date input, hide validation error notification if user writes correct date
			setTimeout(function() {
			   $('.btn-loader').removeClass('loading');
			}, 500);
		}

	});
	
	
	
	// New Main-menu
	// Advanced crucial Main-menu hovers, triggers and fixed-menu functions
	mainMenuTriggers();
	function mainMenuTriggers() {
		//mobile menu
		$('#mobile-menu-trigger').on('click', function(e) {
			$('#main-menu').toggleClass('mobile-menu-visible');
			$('body').toggleClass('mobile-menu-visible');
		});
	}
	// Transformicons - MENU ICON
	var anchor = document.querySelectorAll('a.mobile-menu-trigger');
	[].forEach.call(anchor, function(anchor){
		  var open = false;
		  anchor.onclick = function(event){
			event.preventDefault();
			if(!open){
			  this.classList.add('transformed');
			  open = true;
			}
			else{
			  this.classList.remove('transformed');
			  open = false;
			}
		  }
	}); 
	
	
			
});


//input type file styled
function inputFile(){
	var fileInput = $('#file:file');
	document.getElementById("file").value = ""; //reset on refresh
	
	fileInput.change(function(){
		var path = $(this).val();
		var filename = path.replace(/^.*\\fakepath\\/, "");
 
		$('#file-name').text('Załącznik: '+ filename);
	})

	$('.add-file-label').click(function(){
		fileInput.click();
	}).show();
}


// date validator
function isValidDate(controlName, format){
    var isValid = true;
    try{
        jQuery.datepicker.parseDate(format, jQuery('#' + controlName).val(), null);
    }
    catch(error){
        isValid = false;
    }
    return isValid;
}



// watch msg textarea, hide validation error notification if user writes at least 3 words
function isValidMsgOnTyping() {
	$("#message").keyup(function() {
		if ( $('#message').val().split(/\s+/).length >= 3) { 
			$("#message").unbind("keyup");
			$('#message_error').addClass('hide');
		}
	});
}


// watch date input, hide validation error notification if user writes correct date
function isValidDateOnTyping() {
	$("#oc_end_date").keyup(function() {
		if ( isValidDate("oc_end_date", "dd/mm/yy") === true ) { 
			$("#oc_end_date").unbind("keyup");
			$('#oc_end_date_error').addClass('hide');
		}
	});
}
