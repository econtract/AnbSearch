//Function copied from https://stackoverflow.com/questions/31075133/strip-duplicate-parameters-from-the-url
function stripUriParams(uri) {
    var stuff = decodeURIComponent(uri);
    var pars = stuff.split("&");
    var finalPars = [];
    var comps = {};
    for (var i = pars.length - 1; i >= 0; i--)
    {
        spl = pars[i].split("=");
        //ignore arrays
        if(!spl[0].endsWith(']')) {
            comps[spl[0]] = spl[1];
        } else {
            //this is array so enter it into final url array
            finalPars.push(spl[0] + "=" + spl[1]);
        }
    }
    for (var a in comps)
        finalPars.push(a + "=" + comps[a]);
    url = finalPars.join('&');
    return url;
}

function removeDuplicatesFromUri(uri) {
    //remove any duplicate params
    /*var redToArr = uri.split('&');

    //remove duplicates with lodash
    if(typeof _ != "undefined"){
        redToArr = _.uniq(redToArr);
    }*/
    var finalRedirect = stripUriParams(uri);
    finalRedirect = '?' + finalRedirect;
    return finalRedirect;
}

function wizardProfileFormSubmitRedirect() {
    var searchFilterNav = jQuery('#searchFilterNav').serialize();
    var yourProfileWizardForm = jQuery('#yourProfileWizardForm').serialize();

    var redirectTo = yourProfileWizardForm + '&' + searchFilterNav + '&searchSubmit=&profile_wizard=';
    var finalRedirect = removeDuplicatesFromUri(redirectTo);
    return finalRedirect;
}

function getFirstUrlParamByName(name, uri) {

    var match = RegExp('[&]' + name + '=([^&]*)')
        .exec(uri);

    return match && decodeURIComponent(match[1].replace(/\+/g, ' '));

}

function appendMoreResultsInUrl(url) {
    url = url.toString();
    //check if redirectUrl already doesn't has more results option
    if(url.indexOf("more_results") !== -1) {
        //do nothing
    } else {
        url += '&more_results=true';
    }

    return url;
}

function getRedirectUrl() {
    var redirectUrl = "";
    if(location.search.indexOf('profile_wizard') >= 0) {
        redirectUrl = wizardProfileFormSubmitRedirect();
    } else {
        redirectUrl = jQuery('#searchFilterNav').serialize();
    }

    if(window.location.toString().indexOf("more_results") !== -1) {
        redirectUrl = appendMoreResultsInUrl(redirectUrl);
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
        $.get(search_compare_obj.ajax_url+urlParams, data, function(response) {

            $('.resultsData').html(response);
            $('.loadMore').hide();

        });

        window.history.replaceState(null, null, appendMoreResultsInUrl(window.location));
    });

    //if load_more=true then trigger click on .loadMore, to ensure that user don't click again and again on load more
    if(window.location.toString().indexOf("more_results") !== -1) {
        $('.loadMore').trigger('click');
    }

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
        //console.log(redirectUrl);
        window.location = redirectUrl;
    });

    $("#calcPbsModal").on("show.bs.modal", function(e) {
        var link = $(e.relatedTarget);
        var target = $(this);
        console.log("search_compare_obj***", search_compare_obj);
        target.find('.modal-body').html('<div class="ajaxIconWrapper"><div class="ajaxIcon"><img src="'+search_compare_obj.template_uri+'/images/common/icons/ajaxloader.png" alt="Loading..."></div></div>');
        $.get(search_compare_obj.ajax_url, link.attr("href"), function(response) {

            target.find(".modal-body").html(response);

        });
    });

});

