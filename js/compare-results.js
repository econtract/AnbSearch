
jQuery(document).ready(function($){

    var selectedProducts = [];
    var selectedProductTypes = [];

    $('body .rightPanel').on('change', '.comparePackage input[type=checkbox]', function() {

        var selected = [];
        var captureProductTypes = [];

        $('.comparePackage input:checked').each(function() {
            var value = $(this).val();
            var productType = $(this).siblings('input[type="hidden"]').val();


            if(selected.indexOf(value) === -1){
                selected.push(value);
            }

            if(captureProductTypes.indexOf(productType) === -1){
                captureProductTypes.push(productType);
            }
        });

        // to make it access in other object assign to array
        selectedProducts = selected;
        selectedProductTypes = captureProductTypes;
    });

    $('.telecomResults #compareResultsBtn').on('click', function() {

        var data = {
            'action'       : 'compareBetweenResults',
            'products'     :  selectedProducts,
            'productTypes' :  selectedProductTypes,
            'features_label': compare_between_results_object.features_label,
            'lang': compare_between_results_object.lang,
            'telecom_trans': compare_between_results_object.telecom_trans,
            'brands_trans': compare_between_results_object.brands_trans,
            'selected_pack' :  compare_between_results_object.selected_pack,
            'change_pack' :  compare_between_results_object.change_pack,
            'trans_ontime_total' : compare_between_results_object.trans_ontime_total,
            'customer_score' : compare_between_results_object.customer_score,
            'trans_installation' : compare_between_results_object.trans_installation,
            'trans_free_activation' : compare_between_results_object.trans_free_activation,
            'trans_free_modem' : compare_between_results_object.trans_Free_modem,
            'trans_your_advantage' : compare_between_results_object.trans_your_advantage,
            'trans_order_now' : compare_between_results_object.trans_order_now,
            'trans_info_options' : compare_between_results_object.trans_info_options,
            'trans_mth' : compare_between_results_object.trans_mth,
            'trans_free' : compare_between_results_object.trans_free,
            'trans_free_installation' : compare_between_results_object.trans_free_installation,
            'trans_activation' : compare_between_results_object.trans_activation,
            'trans_modem' : compare_between_results_object.trans_modem,

    };

        var urlParams = window.location.search;
        $('#compareBetweenResultsResponse').html('<div class="ajaxIconWrapper"><div class="ajaxIcon"><img src="'+compare_between_results_object.template_uri+'/images/common/icons/ajaxloader.png" alt="Loading..."></div></div>');
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.get(compare_between_results_object.site_url+'/api/' + urlParams+'&load=compare', data, function(response) {
            $('#compareBetweenResultsResponse').html(response);
            fixDealsTableHeight($('.compareSection .dealsTable.grid'));
        });
    });

    // Call for pop suppliers dropDown
    $('#currentProvider').on('change', function() {

        var data = {
            'action'   : 'productsCallback',
            'supplier' : this.value,
            'telecom_trans': compare_between_results_object.telecom_trans,
            'brands_trans': compare_between_results_object.brands_trans,
            'lang': compare_between_results_object.lang,
        };

        var currentPack= $('#currentPack');
        //var firstOption = '<option value="">'+compare_between_results_object.select_your_pack+"</option>";
        var firstOption = '';

        var urlParams = window.location.search
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        currentPack.html('<option value="">Loading...</option>');

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

    // Get current pack product
    $('#comparePopupForm').on('submit', function(e) {
        e.preventDefault();
        var currentPack = $('#currentPack').val().split('|');
        $('#selectCurrentPack').modal('hide');
        var data = {
            'action'       : 'compareBetweenResults',
            'productTypes' :  currentPack[0],
            'products'     :  currentPack[1],
            'crntPack'     : compare_between_results_object.current_pack,
            'features_label': compare_between_results_object.features_label,
            'lang': compare_between_results_object.lang,
            'telecom_trans': compare_between_results_object.telecom_trans,
            'brands_trans': compare_between_results_object.brands_trans,
            'selected_pack' :  compare_between_results_object.selected_pack,
            'change_pack' :  compare_between_results_object.change_pack,
            'trans_ontime_total' : compare_between_results_object.trans_ontime_total,
            'customer_score' : compare_between_results_object.customer_score,
            'trans_installation' : compare_between_results_object.trans_installation,
            'trans_free_activation' : compare_between_results_object.trans_free_activation,
            'trans_free_modem' : compare_between_results_object.trans_Free_modem,
            'trans_your_advantage' : compare_between_results_object.trans_your_advantage,
            'trans_order_now' : compare_between_results_object.trans_order_now,
            'trans_info_options' : compare_between_results_object.trans_info_options,
            'trans_mth' : compare_between_results_object.trans_mth,
            'trans_free' : compare_between_results_object.trans_free,
            'trans_free_installation' : compare_between_results_object.trans_free_installation,
            'trans_activation' : compare_between_results_object.trans_activation,
            'trans_modem' : compare_between_results_object.trans_modem,

        };

        var urlParams = window.location.search;
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        $('#crntPackSelectionSection .offer').append('<div class="ajaxIconWrapper"><div class="ajaxIcon"><img src="'+compare_between_results_object.template_uri+'/images/common/icons/ajaxloader.png" alt="Loading..."></div></div>');
        jQuery.get(compare_between_results_object.site_url+'/api/' + urlParams+'&load=compare', data, function(response) {

            $('#crntPackSelectionSection').hide();
            $('#crntPackSelectionResponse').html(response).show();
            $('#crntPackSelectionSection .offer .ajaxIconWrapper').remove();//Removing loaders ones result is loaded

            fixDealsTableHeight($('.compareSection .dealsTable.grid'));
        });

        return false;
    });

    // Close Current Pack
    $('body #compareSearch').on('click', '.offer-col a.close', function(e){
        e.preventDefault();

        var _self = $(this);

        _self.parents('.offer-col')
            .removeClass('selected')
            .addClass('unselected');

        if ($("#crntPackSelectionSection").is(':hidden')) {

            $('#crntPackSelectionSection').show();
            $('#crntPackSelectionResponse').hide();
        }
    });

});

