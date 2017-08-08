function removeDuplicatesFromUri(uri) {
    //remove any duplicate params
    var redToArr = uri.split('&');
    //remove duplicates with lodash
    if(typeof _ != "undefined"){
        redToArr = _.uniq(redToArr);
    }
    var finalRedirect = '?' + redToArr.join('&');
    return finalRedirect;
}

function wizardProfileFormSubmitRedirect() {
    var searchFilterNav = jQuery('#searchFilterNav').serialize();
    var yourProfileWizardForm = jQuery('#yourProfileWizardForm').serialize();

    var redirectTo = yourProfileWizardForm + '&' + searchFilterNav + '&searchSubmit=&profile_wizard=';
    var finalRedirect = removeDuplicatesFromUri(redirectTo);
    return finalRedirect;
}

function getRedirectUrl() {
    var redirectUrl = "";
    if(location.search.indexOf('profile_wizard') >= 0) {
        redirectUrl = wizardProfileFormSubmitRedirect();
    } else {
        redirectUrl = '?' + jQuery('#searchFilterNav').serialize();
    }

    return redirectUrl;
}

jQuery(document).ready(function($){

    $('.loadMore').on('click', function() {
        var data = {
            'action': 'moreResults'
        };
        var urlParams = window.location.search;

        $('.loadMore').html('LOADING...');
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        $.get(load_more_object.ajax_url+urlParams, data, function(response) {

            $('.resultsData').html(response);
            $('.loadMore').hide();

        });
    });

    //Search results wizard your profile popup
    $('#yourProfileWizardForm').on('submit', function(e){
        e.preventDefault();

        window.location = wizardProfileFormSubmitRedirect();
    });

    //sort feature
    $('#sortResults').on('change', function() {
        var sortBy = $(this).val();
        var redirectUrl = getRedirectUrl() + '&sort='+sortBy+'&searchSubmit=';
        redirectUrl = removeDuplicatesFromUri(redirectUrl);
        window.location = redirectUrl;
    });

    //filter results left nav
    $('#searchFilterNav').on('submit', function(e) {
        e.preventDefault();
        var redirectUrl = getRedirectUrl() + '&searchSubmit=';
        var sortBy = $('#sortResults').val();
        if(typeof sortBy != "undefined") {
            redirectUrl += '&sort='+sortBy+'&searchSubmit=';
        }
        redirectUrl = removeDuplicatesFromUri(redirectUrl);
        window.location = redirectUrl;
    });
});

