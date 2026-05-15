(function ($) {
    "use strict";





    $('button[x-url]').on('click', x_url_request_func );

    function _x_ajax( url, data, button ) {
        button.attr('disabled',"disabled");
        var cb = button.attr('x-callback');
        $.ajax({
            url     : url,
            method  : "POST",
            dataType: "json",
            data    : data,
            success : function(response) {
                button.attr('disabled',false);
                if (response.success) {
                    if ( response.data.reload ) {
                        if (typeof response.data.reload === 'string' || response.data.reload instanceof String) { window.location.href = response.data.reload; }
                        else { location.reload(); }
                    }
                }
                if (cb) { window[cb].call(button, response); }
            },
            error: function () {
                button.attr('disabled',false);
                if (cb) { window[cb].call(button, "Fatal error"); }
            },
            complete: function( ) { button.attr('disabled',false); }
        });
    }

    function x_url_request_func(event) {
        var obj = $(this),
            url = obj.attr('x-url'),
            attrs = obj[0].attributes,
            data = {};

        for (var key in attrs) {
            if (attrs[key].nodeName && attrs[key].nodeName.indexOf('x-data-')==0) {
                data[ attrs[key].nodeName.substring(7) ] = attrs[key].nodeValue;
            }
        }
        _x_ajax(url, data, obj);
        return false;
    }



    $('input[type="email"]').keyup( function() {
        var re = isValidEmail($(this).val())
        if (!re) { $(this).addClass('error'); }
        else { $(this).removeClass('error'); }
    });


    $('.write-to-clipboard').on('click', function() {
        // https://stackoverflow.com/questions/51805395/navigator-clipboard-is-undefined
        try {
            if (navigator.clipboard.writeText) {
                navigator.clipboard.writeText( $(this).attr('data-clipboard-text') );
            }

            var tooltip_element = $(this).find('[data-bs-toggle="tooltip"]').first().get(0);
            var copied_tooltip = new bootstrap.Tooltip(tooltip_element, { delay: {"show":10, "hide":1500} } );
            tooltip_element.addEventListener('hidden.bs.tooltip', () => { copied_tooltip.dispose() })
            copied_tooltip.show();
        }
        catch (err) { console.error(err); }
    });





})(jQuery);




// ============================================================================
// =========================== HELPING FUNCTIONS ==============================
// ============================================================================

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}


function get_user_info() {
    var result = {
        screen: null,
        browser: null,
        os: null,
        is_mobile: false,
        cookies: false,
    };

    // screen
    if (screen.width) {
        result['screen'] = {'height': screen.height, 'width':screen.width, };
    }

    // browser
    var nVer = navigator.appVersion;
    var nAgt = navigator.userAgent;
    var browser_name = null;
    var browser_version = null;
    var browser_major_version = parseInt(navigator.appVersion, 10);
    var nameOffset, verOffset, ix;

    if ((verOffset = nAgt.indexOf('Opera')) != -1) {
        browser_name = 'Opera';
        browser_version = nAgt.substring(verOffset + 6);
        if ((verOffset = nAgt.indexOf('Version')) != -1) { browser_version = nAgt.substring(verOffset + 8); }
    }
    else if ((verOffset = nAgt.indexOf('OPR')) != -1) {
        browser_name = 'Opera';
        browser_version = nAgt.substring(verOffset + 4);
    }
    else if ((verOffset = nAgt.indexOf('Edge')) != -1) {
        browser_name = 'Microsoft Legacy Edge';
        browser_version = nAgt.substring(verOffset + 5);
    }
    else if ((verOffset = nAgt.indexOf('Edg')) != -1) {
        browser_name = 'Microsoft Edge';
        browser_version = nAgt.substring(verOffset + 4);
    }
    else if ((verOffset = nAgt.indexOf('MSIE')) != -1) {
        browser_name = 'Microsoft Internet Explorer';
        browser_version = nAgt.substring(verOffset + 5);
    }
    else if ((verOffset = nAgt.indexOf('Chrome')) != -1) {
        browser_name = 'Chrome';
        browser_version = nAgt.substring(verOffset + 7);
    }
    else if ((verOffset = nAgt.indexOf('Safari')) != -1) {
        browser_name = 'Safari';
        browser_version = nAgt.substring(verOffset + 7);
        if ((verOffset = nAgt.indexOf('Version')) != -1) { browser_version = nAgt.substring(verOffset + 8); }
    }
    else if ((verOffset = nAgt.indexOf('Firefox')) != -1) {
        browser_name = 'Firefox';
        browser_version = nAgt.substring(verOffset + 8);
    }
    else if (nAgt.indexOf('Trident/') != -1) {
        browser_name = 'Microsoft Internet Explorer';
        browser_version = nAgt.substring(nAgt.indexOf('rv:') + 3);
    }
    else if ((nameOffset = nAgt.lastIndexOf(' ') + 1) < (verOffset = nAgt.lastIndexOf('/'))) {
        browser_name = nAgt.substring(nameOffset, verOffset);
        browser_version = nAgt.substring(verOffset + 1);
        if (browser_name.toLowerCase() == browser_name.toUpperCase()) { browser_name = navigator.appName; }
    }

    if ((ix = browser_version.indexOf(';')) != -1) browser_version = browser_version.substring(0, ix);
    if ((ix = browser_version.indexOf(' ')) != -1) browser_version = browser_version.substring(0, ix);
    if ((ix = browser_version.indexOf(')')) != -1) browser_version = browser_version.substring(0, ix);

    browser_major_version = parseInt('' + browser_version, 10);
    if (isNaN(browser_major_version)) {
        browser_version = '' + parseFloat(navigator.appVersion);
        browser_major_version = parseInt(navigator.appVersion, 10);
    }

    result['browser'] = {'name': browser_name, 'version':browser_version, 'major':browser_major_version };

    // mobile or desktop
    result['is_mobile'] = /Mobile|mini|Fennec|Android|iP(ad|od|hone)/.test(nVer);

    // cookie
    var cookieEnabled = (navigator.cookieEnabled) ? true : false;
    if (typeof navigator.cookieEnabled == 'undefined' && !cookieEnabled) {
        document.cookie = 'testcookie';
        cookieEnabled = (document.cookie.indexOf('testcookie') != -1) ? true : false;
    }
    result['cookies'] = cookieEnabled;

    // system
    var os_name = null;
    var clientStrings = [
        {s:'Windows 10', r:/(Windows 10.0|Windows NT 10.0)/},
        {s:'Windows 8.1', r:/(Windows 8.1|Windows NT 6.3)/},
        {s:'Windows 8', r:/(Windows 8|Windows NT 6.2)/},
        {s:'Windows 7', r:/(Windows 7|Windows NT 6.1)/},
        {s:'Windows Vista', r:/Windows NT 6.0/},
        {s:'Windows Server 2003', r:/Windows NT 5.2/},
        {s:'Windows XP', r:/(Windows NT 5.1|Windows XP)/},
        {s:'Windows 2000', r:/(Windows NT 5.0|Windows 2000)/},
        {s:'Windows ME', r:/(Win 9x 4.90|Windows ME)/},
        {s:'Windows 98', r:/(Windows 98|Win98)/},
        {s:'Windows 95', r:/(Windows 95|Win95|Windows_95)/},
        {s:'Windows NT 4.0', r:/(Windows NT 4.0|WinNT4.0|WinNT|Windows NT)/},
        {s:'Windows CE', r:/Windows CE/},
        {s:'Windows 3.11', r:/Win16/},
        {s:'Android', r:/Android/},
        {s:'Open BSD', r:/OpenBSD/},
        {s:'Sun OS', r:/SunOS/},
        {s:'Chrome OS', r:/CrOS/},
        {s:'Linux', r:/(Linux|X11(?!.*CrOS))/},
        {s:'iOS', r:/(iPhone|iPad|iPod)/},
        {s:'Mac OS X', r:/Mac OS X/},
        {s:'Mac OS', r:/(Mac OS|MacPPC|MacIntel|Mac_PowerPC|Macintosh)/},
        {s:'QNX', r:/QNX/},
        {s:'UNIX', r:/UNIX/},
        {s:'BeOS', r:/BeOS/},
        {s:'OS/2', r:/OS\/2/},
        {s:'Search Bot', r:/(nuhk|Googlebot|Yammybot|Openbot|Slurp|MSNBot|Ask Jeeves\/Teoma|ia_archiver)/}
    ];
    for (var id in clientStrings) {
        var cs = clientStrings[id];
        if (cs.r.test(nAgt)) {
            os_name = cs.s;
            break;
        }
    }

    var os_version = null;
    if (/Windows/.test(os_name)) {
        os_version = /Windows (.*)/.exec(os_name)[1];
        os_name = 'Windows';
    }
    try {
        switch (os_name) {
            case 'Mac OS':
            case 'Mac OS X':
            case 'Android':
                os_version = /(?:Android|Mac OS|Mac OS X|MacPPC|MacIntel|Mac_PowerPC|Macintosh) ([\.\_\d]+)/.exec(nAgt)[1];
                break;
            case 'iOS':
                os_version = /OS (\d+)_(\d+)_?(\d+)?/.exec(nVer);
                os_version = os_version[1] + '.' + os_version[2] + '.' + (os_version[3] | 0);
                break;
        }
    }
    catch(error) { os_version = null; }

    result['os'] = {'name': os_name, 'version': os_version }

    return result;
}