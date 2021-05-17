var filtersApplied = getParameterByName('filters_applied');

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

function prependQueryStringQuestionMark(finalRedirect) {
    if(finalRedirect.indexOf('?') === -1) {
        finalRedirect = '?' + finalRedirect;
    }

    return finalRedirect;
}

function getFirstUrlParamByName(name, uri) {

    var match = RegExp('[&]' + name + '=([^&]*)')
        .exec(uri);

    return match && decodeURIComponent(match[1].replace(/\+/g, ' '));

}

function bypassWizardExistingOkButton(currSubsec) {
    var bypassExistingOk = false;
    currSubsec.find('input:not(:radio):not(:checkbox), ' +
        'input:radio:checked, ' +
        'input:checkbox:checked').each(function () {
        var currInput = jQuery(this);
        var val = currInput.val().trim();
        if((currInput.prop('type') === 'text' || currInput.prop('type') === 'number') && !_.isEmpty(val)) {
            //for text inputs consider 0 as empty as well
            if(currInput.val().trim() != '0') {
                bypassExistingOk = true;
                currSubsec.addClass('bypass-ok');
            }
        }
        else if(currInput.prop('type') !== 'text' && currInput.prop('type') !== 'number' && !_.isEmpty(val)) {
            bypassExistingOk = true;
            currSubsec.addClass('bypass-ok');
        }
    });

    return bypassExistingOk;
}

function adjustPersonalSettingScenarios() {
    //Scenarios for personal settings popup
    //Incase its openend after 1st submission
    filtersApplied = getParameterByName('filters_applied');//check if the filters are applied
    //alert(filtersApplied);
    if(filtersApplied) {//this means that form has already been submitted once
        //now find out if all the sub-sections were filled or something wasn't

        //in case all sub-sections were filled and main button clicked, this is time to keep the sub-sections in default form,
        //which is first one is opened and rest are closed
        //and in case user open any sub-section and presses okay that section will not trigger the next sub-section,
        //that will in fact close the current one

        //and in case user clicks on change, then the appropriate section will get open
        //and pressing ok will also not trigger the next sub-section it'll only close current one

        //in case some of the sub-sections were missed and main button was clicked, open the first unfilled sub-section
        //pressing ok will oen the next unfilled sub-section

        //and in case user clicks on change, then the appropriate section will get open
        //and pressing ok will open the next unfilled sub-section and so on
        jQuery('#yourProfileWizardForm .panel').each(function() {
            var currSubsec = jQuery(this);
            var bypassExistingOk = bypassWizardExistingOkButton(currSubsec);

            if(bypassExistingOk) {
                currSubsec.find('.panel-body .buttonWrapper button').off('click');

                //time to inject our own click event on this button
                currSubsec.find('.buttonWrapper button').on('click', function() {
                    //now click the first anchor child to close this sub-section
                    setTimeout(function(){ currSubsec.find('.panel-title a').trigger('click'); }, 20);
                    //now decide based on the fact that whole sub-sections are filled or not, if yes do nothing if now,
                    //open next unfilled sub-section
                    var firstUnfilledSubsec = jQuery(currSubsec).nextAll('.panel:not(.bypass-ok)').first();
                    //firstUnfilledSubsec.find('.panel-title a').trigger('click');
                    firstUnfilledSubsec.find('.panel-title a').trigger('click');
                    //first step is to find out the unfilled sections
                });
            }
        });
    }
}

jQuery(document).ready(function($){
    //Adjust the personal settings scenarios on first load
    adjustPersonalSettingScenarios();

    //Adjust the personal settings when somebody changes the sub-sections
    jQuery('#yourProfileWizardForm .panel').on('change', function () {
        adjustPersonalSettingScenarios();
    });
});

