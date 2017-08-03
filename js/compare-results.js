
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

});

