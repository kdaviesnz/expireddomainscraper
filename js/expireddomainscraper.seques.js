
var expiredDomainTableSegue = function(page)
{
    return function(e) {
        console.log('expiredDomainTableSegue() page=' + page);
        $('.edsGetNext').prop('disabled', true);
        $('.edsGetPrevious').prop('disabled', true);
        var callback = renderExpiredDomainTable(page);
        getExpiredDomains(30, callback, page);
    }
}

var getNextSegue = function(page){
    return function(e) {
        e.preventDefault();
        abortAjax();
        expiredDomainTableSegue(page)(null);
    }
}

var getPreviousSegue = function(page){
    return function(e) {
        e.preventDefault();
        abortAjax();
        expiredDomainTableSegue(page)(null);
    }
}

var expiredDomainFetchLastestSegue = function()
{
    return function(e) {
        console.log("Calling expiredDomainFetchLatestSegue()");
        e.preventDefault();
        abortAjax();
        $('#progress').html('Importing domains ... please wait, this may take awhile.');
        // tdnam_all_listings and tdnam_all_listings2 take at least 15mins each and so aren't included
        // getLatestDomains('5_letter_auctions'),
        //     getLatestDomains('auction_end_tomorrow'),
        //     getLatestDomains('traffic200'),
        //     getLatestDomains('traffic'),
        //     getLatestDomains('bidding_service_auctions'),
        //     getLatestDomains('tdnam_all_listings3')

        $.when(
            getLatestDomains('closeouts')
       ).done(function(e1, e2, e3, e4, e5, e6, e7) {
            // e1, e2 etc are arguments resolved for the ajax requests.
            // Each argument is an array with the following structure: [ data, statusText, jqXHR ]
            expiredDomainTableSegue(1)(null);
            $('#progress').html('');
            alert('Importing complete');
        });
    }
}