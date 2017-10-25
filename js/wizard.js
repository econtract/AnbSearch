 window.wizardAjaxCall = function() {
    var searchWizardForm = jQuery('#searchWizardForm').serialize();

    var data = {
        'action'       : 'getCompareResultsForWizard'
    };

    var urlParams = searchWizardForm  + '&searchSubmit=1';
    var url = wizard_object.ajax_url+ '?' + urlParams;

    // We can also pass the url value separately from ajaxurl for front end AJAX implementations
    jQuery.get(url, data, function(response) {

        //  console.log(response, "dddd");
        jQuery('.ribbonContent  .counter').html(response);
    });
}

jQuery(document).ready(function($){
    /**
     * Wizard Section
     *
     * capture all events on wizard page to get records from compare API according to user input
     *
     * mousedown mouseup focus blur keydown change (these events also can be captured)
     */
    /*$('body .questionPanel').on("click", '.wizardConatiner',function(e){
       // console.log(e);

        wizardAjaxCall();
    });*/



});

