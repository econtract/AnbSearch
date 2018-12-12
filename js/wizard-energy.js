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
    })

});

