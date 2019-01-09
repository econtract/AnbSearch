jQuery(document).ready(function($){
    $('.loadMoreEnergy').on('click', function() {
        var data = {
            'action': 'moreResultsEnergy'
        };
        var urlParams = window.location.search;

        $('.loadMoreEnergy').html(search_compare_obj_energy.trans_loading_dots);
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        console.log(search_compare_obj_energy.ajax_url+urlParams);
        console.log(data);
        $.get(search_compare_obj_energy.ajax_url+urlParams, data, function(response) {

            $('.resultsData').html(response);
            $('.loadMoreEnergy').hide();

        });

        window.history.replaceState(null, null, appendMoreResultsInUrl(window.location));
    });

    //filter results left nav
    /*$('#searchFilterNav').on('submit', function(e) {
        e.preventDefault();
        $('#wizard_popup_pref_cs').html('');//remove all pref_cs from wizard popup as at this moment they are passed from search navigation
        var redirectUrl = getRedirectUrl() + '&searchSubmit=';
        var sortBy = $('#sortResults').val();
        if(typeof sortBy != "undefined") {
            redirectUrl += '&sort='+sortBy+'&searchSubmit=';
        }
        redirectUrl = prependQueryStringQuestionMark(removeDuplicatesFromUri(redirectUrl));
        window.location = redirectUrl;
    });*/

    $('body').on('click','#searchFilterNav .collapseOptions .selectedOptions a[data-panel], #searchFilterNav a.forceOpen[data-panel]', function (e) {
        e.preventDefault();

        var _self = $(this);
        var panelID = "#"+_self.data('panel')+"Panel";
        var profileModal = $('#updateProfileEnergy');

        profileModal.modal('show');
        profileModal
            .find(panelID +' .panel-heading:not(.active) .panel-title a')
            .trigger('click');
    });

    //To fix weired issue of button allignment, which was moving right on imran's machine on FF v61.0.1.
    $("#updateProfileEnergy").on('shown.bs.modal', function () {
        $('#mini_wizard_submit_btn').css('position', 'relative');
    });
});

