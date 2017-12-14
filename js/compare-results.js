
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

    $('#compareResultsBtn').on('click', function() {

        var data = {
            'action'       : 'compareBetweenResults',
            'products'     :  selectedProducts,
            'productTypes' :  selectedProductTypes
        };

        var urlParams = window.location.search;
        $('#compareBetweenResultsResponse').html('<div class="ajaxIconWrapper"></div><div class="ajaxIcon"><img src="'+compare_between_results_object.template_uri+'/images/common/icons/ajaxloader.png" alt="Loading..."></div></div>');
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.get(compare_between_results_object.ajax_url + urlParams, data, function(response) {

            $('#compareBetweenResultsResponse').html(response);
        });
    });

    // Call for pop suppliers dropDown
    $('#currentProvider').on('change', function() {

        var data = {
            'action'   : 'productsCallback',
            'supplier' : this.value
        };

        var urlParams = window.location.search
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        $('#currentPack').html('<option>Loading...</option>');

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.get(compare_between_results_object.ajax_url + urlParams, data, function(response) {

            $('#currentPack').html(response);
        });
    });

    // Get current pack product
    $('#currentPackBtn').on('click', function() {

        var currentPack = $('#currentPack').val().split('|');

        var data = {
            'action'       : 'compareBetweenResults',
            'productTypes' :  currentPack[0],
            'products'     :  currentPack[1],
            'crntPack'     : compare_between_results_object.current_pack
        };

        var urlParams = window.location.search;
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        $('#crntPackSelectionSection').html('<div class="ajaxIconWrapper"></div><div class="ajaxIcon"><img src="'+compare_between_results_object.template_uri+'/images/common/icons/ajaxloader.png" alt="Loading..."></div></div>');
        jQuery.get(compare_between_results_object.ajax_url + urlParams, data, function(response) {

            $('#crntPackSelectionSection').hide();
            $('#crntPackSelectionResponse').html(response).show();
        });
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

