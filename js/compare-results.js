
jQuery(document).ready(function($){

    // Call for pop suppliers dropDown
    $('#currentProvider').on('change', function() {
        var data = {
            'action'   : 'productsCallback',
            'supplier' : this.value,
            'telecom_trans': compare_between_results_object.telecom_trans,
            'brands_trans': compare_between_results_object.brands_trans,
            'lang': compare_between_results_object.lang,
            'select_your_pack' :  compare_between_results_object.select_your_pack,
            'i_dont_know_contract' : compare_between_results_object.trans_idontknow,
        };
        var currentPack= $('#currentPack');
        //var firstOption = '<option value="">'+compare_between_results_object.select_your_pack+"</option>";
        var firstOption = '';

        var urlParams = window.location.search
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        currentPack.html('<option value="">'+compare_between_results_object.trans_loading_dots+'</option>');

        //if there is no sg fetch that from the popup
        if(!_.includes(urlParams, 'sg')) {
            urlParams += '&sg='+$('#currentpack_sg').val();
        }

        if(!_.includes(urlParams, 'cat') && $('#check_energy').val() == 'request_from_energy') {
            urlParams += '&cat='+$('#currentpack_cat').val();
        }

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.get(compare_between_results_object.site_url+'/api/' + urlParams+'&load=compare', data, function(response) {

            currentPack.html(firstOption +""+response);
            currentPack
                .siblings('.form-control-feedback')
                .removeClass('glyphicon-ok glyphicon-remove');

            currentPack.parents('form').validator('update');
        });
    });
});
