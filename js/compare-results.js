
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
            'productTypes' :  selectedProductTypes,
            'features_label': compare_between_results_object.features_label,
            'lang': compare_between_results_object.lang
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
            'supplier' : this.value
        };

        var currentPack= $('#currentPack');
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
            'lang': compare_between_results_object.lang
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

