
jQuery(document).ready(function($){
    function getPacksEnergy(currentObj) {
        var data = {
            'action'   : 'productsCallback',
            'supplier' : currentObj.value,
            'telecom_trans': compare_between_results_object.telecom_trans,
            'brands_trans': compare_between_results_object.brands_trans
        };

        var currentPack= $('#currentPackEnergy');
        var firstOption = '<option value="">'+compare_between_results_object.select_your_pack+"</option>";

        var urlParams = window.location.search
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        currentPack.html('<option value="">Loading...</option>');

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.get(compare_between_results_object.site_url+'/api/' + urlParams+'&load=ajax', data, function(response) {

            currentPack.html(firstOption +""+response);
            currentPack
                .siblings('.form-control-feedback')
                .removeClass('glyphicon-ok glyphicon-remove');

            currentPack.parents('form').validator('update');
        });
    }

    // Call for pop suppliers dropDown
    $('#currentProviderEnergy').on('change', function() {
        getPacksEnergy(this);
    });

    $(document).ready(function() {
        var currentEnergyProvider = $('#currentProviderEnergy').val();

        if(!_.isEmpty(currentEnergyProvider)) {
            setTimeout(function() {
                $('#currentProviderEnergy').trigger('change');
            }, 50);
        }

        var currentProvider = $('#currentProvider').val();

        if(!_.isEmpty(currentProvider)) {
            setTimeout(function() {
                $('#currentProvider').trigger('change');
            }, 50);
        }
    })
});

