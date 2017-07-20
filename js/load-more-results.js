
jQuery(document).ready(function($){

    $('.loadMore').on('click', function() {
        var data = {
            'action': 'moreResults'
        };
        var urlParams = window.location.search;

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.get(load_more_object.ajax_url+urlParams, data, function(response) {

            $('.resultsData').html(response);
            $('.loadMore').hide();

        });
    });
});

