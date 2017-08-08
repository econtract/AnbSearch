
jQuery(document).ready(function($){

    var selectedProducts = [];
    $('body .rightPanel').on('change', '.comparePackage input[type=checkbox]', function() {

        var selected = [];
        $('.comparePackage input:checked').each(function() {
            selected.push($(this).val());
        });
        // to make it access in other object assign to array
        selectedProducts = selected;
    });

    $('#compareResultsBtn').on('click', function() {

        var data = {
            'action'   : 'compareBetweenResults',
            'products' :  selectedProducts
        };

        var urlParams = window.location.search;

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

        var urlParams = window.location.search;

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.get(compare_between_results_object.ajax_url + urlParams, data, function(response) {

            $('#currentPack').html(response);
        });
    });

    // Get current pack product
    $('#currentPackBtn').on('click', function() {

        var data = {
            'action'   : 'compareBetweenResults',
            'products' :  $('#currentPack').val(),
            'crntPack' : compare_between_results_object.current_pack
        };

        var urlParams = window.location.search;

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.get(compare_between_results_object.ajax_url + urlParams, data, function(response) {

            $('#crntPackSelectionSection').hide();
            $('#crntPackSelectionResponse').html(response).show();
        });
    });


    // Close Current Pack
    $('body #compareSearch').on('click', '.offer-col a.close', function(e){
        e.preventDefault();

        console.log('event calls');

        var _self = $(this);

        _self.parents('.offer-col')
            .removeClass('selected')
            .addClass('unselected');

        if ($("#crntPackSelectionSection").is(':hidden')) {

            $('#crntPackSelectionSection').show();
            $('#crntPackSelectionResponse').hide();
        }
        console.log('event calls at the end');
    });

});

