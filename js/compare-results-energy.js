
jQuery(document).ready(function($){

    function getPacksEnergy(currentObj, providerDropdownId = 'currentPackEnergy') {
        var data = {
            'action'   : 'productsCallback',
            'supplier' : currentObj.value,
            'telecom_trans': compare_between_results_object_energy.telecom_trans,
            'brands_trans': compare_between_results_object_energy.brands_trans,
            'lang': compare_between_results_object_energy.lang,
            'select_your_pack' : compare_between_results_object_energy.select_your_energy_pack,
            'i_dont_know_contract' : compare_between_results_object_energy.trans_idontknow
        };
        var currentPack= $('#'+providerDropdownId);
        var firstOption = '';
        //Commented on request of the client, requested in ticket #RED-3166
        //firstOption = '<option value="pack|i_dnt_know_contract">'+compare_between_results_object_energy.select_your_pack+"</option>";

        var urlParams = window.location.search
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        currentPack.html('<option value="">'+ compare_between_results_object_energy.trans_loading_dots +'</option>');

        if(!_.includes(urlParams, 'sg')) {
            urlParams += '&sg='+$('#currentpack_sg').val();
        }

        if(!_.includes(urlParams, 'cat')) {
            urlParams += '&cat='+$('#currentpack_cat').val();
        }

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.get(compare_between_results_object_energy.site_url+'/api/' + urlParams+'&load=compare', data, function(response) {

            currentPack.html(firstOption +""+response);
            currentPack
                .siblings('.form-control-feedback')
                .removeClass('glyphicon-ok glyphicon-remove');

            currentPack.parents('form').validator('update');
            currentPack.removeClass('hide');
        });
    }

    // Call for pop suppliers dropDown
    $('#currentProviderEnergy').on('change', function() {
        getPacksEnergy(this);
    });

    $('#currentProviderEnergyTop').on('change', function() {
        getPacksEnergy(this, 'currentPackEnergyTop');
    });

    $(document).ready(function() {
        var currentEnergyProvider = $('#currentProviderEnergy').val();

        if(!_.isEmpty(currentEnergyProvider)) {
            setTimeout(function() {
                $('#currentProviderEnergy').trigger('change');
            }, 50);
        }

        var currentEnergyProviderTop = $('#currentProviderEnergyTop').val();

        if(!_.isEmpty(currentEnergyProviderTop)) {
            setTimeout(function() {
                $('#currentProviderEnergyTop').trigger('change');
            }, 50);
        }

        var currentProvider = $('#currentProvider').val();

        if(!_.isEmpty(currentProvider)) {
            setTimeout(function() {
                $('#currentProvider').trigger('change');
            }, 50);
        }
    });
});
