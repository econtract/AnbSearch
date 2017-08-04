function wizardProfileFormSubmitRedirect() {
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
    return finalRedirect;
}

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

        window.location = wizardProfileFormSubmitRedirect();
    });

    //sort feature
    jQuery('#sortResults').on('change', function() {
        var sortBy = $(this).val();
        var redirectUrl = "";
        if(location.search.indexOf('profile_wizard') >= 0) {
            redirectUrl = wizardProfileFormSubmitRedirect();
        } else {
            redirectUrl = '?' + jQuery('#searchFilterNav').serialize()+'&sort='+sortBy+'&searchSubmit=';
        }
        window.location = redirectUrl;
    });
});

