
jQuery(document).ready(function($){

    $('.loadMore').on('click', function() {
        var data = {
            'action': 'moreResults'
        };
        var urlParams = window.location.search;

        $('.loadMore').html('LOADING...');
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.get(load_more_object.ajax_url+urlParams, data, function(response) {

            $('.resultsData').html(response);
            $('.loadMore').hide();

        });
    });

    //Search results wizard your profile popup
    jQuery('#yourProfileWizardForm').on('submit', function(e){
        e.preventDefault();

        var searchFilterNav = jQuery('#searchFilterNav').serialize();
        var yourProfileWizardForm = jQuery('#yourProfileWizardForm').serialize();

        var redirectTo = yourProfileWizardForm + '&' + searchFilterNav + '&searchSubmit=&profile_wizard=';

        //remove any duplicate params
        var redToArr = redirectTo.split('&');
        //remove duplicates with lodash
        if(typeof _ != "undefined"){
            redToArr = _.uniq(redToArr);
        }
        var finalRedirect = '?' + redToArr.join('&');
        window.location = finalRedirect;
    });
});

