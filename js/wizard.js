 window.wizardAjaxCall = function() {
    var searchWizardForm = jQuery('#searchWizardForm').serialize();

    var data = {
        'action'       : 'getCompareResultsForWizard'
    };

    var urlParams = searchWizardForm  + '&searchSubmit=1';
    var url = wizard_object.ajax_url+ '?' + urlParams;

    // We can also pass the url value separately from ajaxurl for front end AJAX implementations
    jQuery.getJSON(url, data, function(response) {

        if (response){
            //console.log(response, "dddd");

            jQuery.each(response.prices, function( index, value ) {
                jQuery('.supplier-offer-'+index).html(wizard_object.offers_msg+" "+wizard_object.currency+" "+ value.price);
            });

        }

        jQuery('.ribbonContent  .counter').html(response.count);

        jQuery.each(response.no_offer_ids, function( index, value ) {
            jQuery('.supplier-offer-'+value).html(wizard_object.no_offers_msg);
        });
    });
}

function notyMessage(message) {
    new Noty({
        type: 'error',
        layout: 'topRight',
        closeWith: ['click', 'button'],
        text: message

    }).show();
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

    $(".btnWizardZip").on('click', function (e) {
        e.preventDefault();

        var zip = $("#wizard-zip").val();
        var sg = $("#wizard-sg").val();

        var _self =  $("#errorInfoWizard");

        if (zip == '') {
            _self.find('#wrongZip').hide();
            _self.find('#emptyZip p').html(wizard_object.zip_empty).show();
            _self.find('#emptyZip').show();
            _self.modal('show');
            return;
        }

        var data = {
            'action': 'verifyWizardZipCode',
            'zip'   : zip
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        $.post(wizard_object.ajax_url, data, function(response) {

            if (response) {
                var formAction = $("#wizardZipForm").attr('action');
                var appendParam = '?zip='+zip+'&sg='+sg;

                window.location.href = formAction + appendParam;
            } else {

                _self.find('#emptyZip').hide();
                _self.find('#wrongZip p').append('<span>  ' + zip + '</span>');
                _self.find('#wrongZip').show();
                _self.modal('show');
            }

        });

    })



});

