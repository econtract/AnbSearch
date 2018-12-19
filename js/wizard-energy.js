//Below empty function is being called in main.js. If removed you will get js errors.
// Same function is used in Telecom Wizard
window.wizardAjaxCall = function() {

}
function notyMessage(message) {
    new Noty({
        type: 'error',
        layout: 'topRight',
        closeWith: ['click', 'button'],
        text: message

    }).show();
}

function addNumberingToQuestions(){
    jQuery('.CheckOutSteps').find('li').first().addClass('active');
    var wizMasterUl = jQuery('.wizMaster.wizMaster-energy');
    wizMasterUl.find('span.bullet').html('');
    wizMasterUl.children('li.step').each(function(index){jQuery(this).find('span.bullet').html(index+1+'.');});
}

jQuery(document).ready(function($){
    //Wizard page numbering through jquery that comes before heading on each section
    addNumberingToQuestions();

    /**
     * Wizard Section
     *
     * capture all events on wizard page to get records from compare API according to user input
     *
     * mousedown mouseup focus blur keydown change (these events also can be captured)
     */

    $(".btnWizardZip").on('click', function (e) {
        e.preventDefault();

        var zip = $("#wizard-zip").val();

        if(zip.indexOf(zip, ' - ') !== -1) {
            zip = zip.split(' - ')[0];
        }

        var sg = $("#wizard-sg").val();

        var _self =  $("#errorInfoWizard");

        if (zip == '') {
            _self.find('#wrongZip').hide();
            _self.find('#emptyZip p').html(wizard_energy_object.zip_empty).show();
            _self.find('#emptyZip').show();
            _self.modal('show');
            return;
        }
            $('.zipWrapper').addClass('hide');
            $('.catWrapper').removeClass('hide');
            setMiddleContent($('.middle-content'));
    });

    $('.showResultsImmediately').on('click', function (e) {
        e.preventDefault();
        jQuery('#searchEnergyWizardPopup').modal('show');
        $(this).parents('form').submit();
    });


    var sliderEnergy = $('.providerStack .bxsliderWizard').bxSlider({
        minSlides: 7,
        maxSlides: 7,
        slideWidth: 74,
        slideMargin: 10,
        ticker: true,
        speed: 7000
    });

    // when search popup shown
    $('#searchEnergyWizardPopup').on('shown.bs.modal', function () {
        // do something…
        sliderEnergy.reloadSlider();

        setTimeout(
            function() {
                window.location = redirectParam;
            }
            , 100);
    });

    $( "#searchEnergyWizardForm" ).submit(function( event ) {

        var _self = $(this);

        event.preventDefault();

        var formParams = $(this).serialize();
        redirectParam = $(this).attr('action') + '?' + formParams  + '&searchSubmit=';

        var openModal = _self.parents('.modal.in');
        if(openModal.length){
            var modalID = openModal.attr('id');
            console.log(modalID);

            openPopup = true;
            popupIDToOpen = 'searchEnergyWizardPopup';

            $('#'+modalID).modal('hide');
        }
        else{
            $('#searchEnergyWizardPopup').modal('show');
        }
    });

});

