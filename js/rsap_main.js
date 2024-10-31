// On page load, check for rfr parameter in URL and store in cookie if present, 
// that way the user can browse the website and come back to the form while
// the referrer affiliate is still tracked

jQuery(document).ready(function (e) {
    var url_string = window.location.href;
    var url = new URL(url_string);
    var referrerCode = url.searchParams.get("rfr");

    if (referrerCode == '' || referrerCode == null) {
        // Do nothing since no referrer code is present in the URL
    } else {
        // Store the affiliate code for 30 days
        rsapCreateCookie('rsap_referrer', referrerCode, 30);
        //console.log(rsapReadCookie('rsap_referrer'));
    }

    // Populate gravity forms hidden field if present
    jQuery('input[type="hidden"][value="RSAP"]').val(rsapReadCookie('rsap_referrer'));
});

// Create new affiliate
jQuery('.rsap_affiliate_form_holder form').submit(function (e) {

    var formFirstName = jQuery(this).find('input[name="firstname"]').val();
    var formLastName = jQuery(this).find('input[name="lastname"]').val();
    var formEmail = jQuery(this).find('input[name="email"]').val();


    var settings = {
        "async": true,
        "crossDomain": true,
        "url": "/wp-json/rsap/v1/affiliate/",
        "method": "POST",
        "headers": {
            "Content-Type": "application/json",
            "cache-control": "no-cache",
        },
        "processData": false,
        "data": "[{\n   \"firstname\": \"" + formFirstName + "\",\n   \"lastname\": \"" + formLastName + "\",\n   \"email\": \"" + formEmail + "\"\n}]"
    }

    jQuery.ajax(settings).done(function (response) {
        //console.log(response);
    });
});

// Increment affiliate sales
jQuery('#' + rsap_script_vars.conversion_form_wrapper_id).find('form').submit(function (e) {

    var url_string = window.location.href;
    var url = new URL(url_string);
    var referrerCode = url.searchParams.get("rfr");
    //console.log(referrerCode);

    // Override referrerCode with the cookie in case the URL token is no longer present
    referrerCode = rsapReadCookie('rsap_referrer');



    var settings = {
        "async": true,
        "crossDomain": true,
        "url": "/wp-json/rsap/v1/affiliate/sale",
        "method": "POST",
        "headers": {
            "Content-Type": "application/json",
            "cache-control": "no-cache",
        },
        "processData": false,
        "data": "[{\n   \"rfr\": \"" + referrerCode + "\"\n}]"
    }

    jQuery.ajax(settings).done(function (response) {
        //console.log(response);
    });
});

// The following code is from https://www.quirksmode.org/js/cookies.html

function rsapCreateCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    } else var expires = "";
    document.cookie = name + "=" + value + expires + "; path=/";
}

function rsapReadCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function rsapEraseCookie(name) {
    createCookie(name, "", -1);
}

// End code from https://www.quirksmode.org/