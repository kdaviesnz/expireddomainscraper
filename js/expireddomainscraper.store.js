
var getExpiredDomains = function (daysLeft, callback, page) {
    $.ajax('api/godaddy/', {  // For POSTS must include trailing /
        'type': 'GET',
        'error': (function (jqXHR, testStatus, errorThrown) {
            console.log('Error');
            console.log(jqXHR);
            console.log(errorThrown);
        }),
        'success': (function (data, textStatus, jqXHR) {
            console.log('Success');
            console.log(jqXHR);
            callback(jqXHR.responseJSON);
        }),
        'statusCode': {
            404: function () {
                alert('No domains found');
                $('.edsGetNext').prop('disabled', true);
            }
        },
        'accepts': 'applications/json',
        'data': 'daysLeft=' + daysLeft + '&page=' + page,
        'dataType': 'json'
    });
};

var getLatestDomains = function (type) {
    console.log("Calling getLastestDomains(): " + type);
    return $.ajax('api/godaddy/', {  // For POSTS must include trailing /
        'type': 'POST',
        'error': (function (jqXHR, testStatus, errorThrown) {
            console.log('Error');
            console.log(jqXHR);
            console.log(errorThrown);
        }),
        'success': (function (data, textStatus, jqXHR) {
            console.log('Successfully imported latest domains ' + type);
            console.log(jqXHR);
        }),
        'statusCode': {
            404: function () {
            }
        },
        'data': 'type=' + type
    });
};

var getDomainSEOData = function (url, callback) {

    var xhr = $.ajax('api/stats/', {  // For POSTS must include trailing /
        'type': 'POST',
        'error': (function (jqXHR, testStatus, errorThrown) {
            console.log('Error');
            console.log(jqXHR);
            console.log(errorThrown);
        }),
        'success': (function (data, textStatus, jqXHR) {
            console.log('Success');
            console.log(jqXHR);
            callback(jqXHR.responseJSON);
        }),
        'statusCode': {
            404: function () {
            }
        },
        'accepts': 'application/json',
        'data': 'url='+url,
        'dataType': 'json'
    });

    ajaxSEOPool.push(xhr);
    
};

