
var renderExpiredDomainTable = function(page)
{
    return function(expiredDomainRecords) {
        // Headings
        var headerData = [];
        var footerData = [];
        var heading = null;
        var firstRow = null;
        if (expiredDomainRecords.length == 0) {
            firstRow = {
                'Domain': null,
                'Time Left': null,
                'DA': null,
                'PA': null,
                'TF': null
            }
        }
        else {
            firstRow = expiredDomainRecords.pop();
            expiredDomainRecords.push(firstRow);
        }

        for (heading in firstRow) {
            if (heading != 'gd_auction_link') {
                headerData.push(heading);
                footerData.push(heading);
            }
        }

        var expiredDomainTable = createTable(headerData, expiredDomainRecords, footerData, (function (table, header, body, footer, bodyData) {
            // Add expired domain table functionality here

        }));

        $('#expiredDomainTableContainer').html('');
        $('#expiredDomainTableContainer').append(expiredDomainTable);

        $('.edsGetNext').off('click');
        $('.edsGetPrevious').off('click');

        $('.edsGetNext').on('click', getNextSegue(page + 1));

        $('.edsGetNext').prop('disabled', expiredDomainRecords.length == 0);

        if (page *1 > 1) {
            console.log('Enabling Previous button');
            $('.edsGetPrevious').prop('disabled', false);
            $('.edsGetPrevious').on('click', getPreviousSegue(page - 1));
        }
        else{
            $('.edsGetPrevious').prop('disabled', true);
        }

    }
};

var getDomainSEODataCallback= function(tableRow)
{
    return function(domainSEOData) {

        /*
         Expected domainSEOData (example):
         {
         "pageRank": 0,
         "siteIndexTotal": 0,
         "searchResultsTotal": 0,
         "backlinksTotal": 0
         [domainAuthority] => 0
         [pageAuthority] => 0
         [trustFlow] => 0
         }
         */
        console.log('getDomainSEODataCallback) Loading SEO data into table');
        $(tableRow).find('td.pageRank').html(domainSEOData['pageRank']);
        $(tableRow).find('td.domainAuthority').html(domainSEOData['domainAuthority']);
        $(tableRow).find('td.pageAuthority').html(domainSEOData['pageAuthority']);
        $(tableRow).find('td.trustFlow').html(domainSEOData['trustFlow']);

    }

}

var createTable = function (headerData, bodyData, footerData, tableFunctionalityCallback) {

    console.log("Calling createTable()");
    console.log('Body data:');
    console.log(bodyData);

    var header = $thead({});
    var body = $tbody({});
    var footer = $tfoot({});

    var i = null;

    // Header
    var headerRow = $tr({});
    $(header).append(headerRow);
    for (i in headerData) {
        $(headerRow).append($th({}, headerData[i] + ""));
    }

    // Body
    var bodyRow = null;
    for (i in bodyData) {
        bodyRow = $tr({});
        $(body).append(bodyRow);
        for (j in bodyData[i]) {
            if (bodyData[i][j] == null) {
                bodyData[i][j] = "";
            }
            switch (j) {
                case 'PR':
                    $(bodyRow).append($td({'_class': 'pageRank', 'className': 'pageRank'}, bodyData[i][j]));
                    break;
                case 'DA':
                    $(bodyRow).append($td({'_class': 'domainAuthority', 'className': 'domainAuthority'}, bodyData[i][j]));
                    break;
                case 'PA':
                    $(bodyRow).append($td({'_class': 'pageAuthority', 'className': 'pageAuthority'}, bodyData[i][j]));
                    break;
                case 'TF':
                    $(bodyRow).append($td({'_class': 'trustFlow', 'className': 'trustFlow'}, bodyData[i][j]));
                    break;
                case `Domain`:
                    $(bodyRow).append($td({'_class': 'trustFlow', 'className': 'trustFlow'},$a({'href': bodyData[i]['gd_auction_link']}, bodyData[i][j])));
                    break;
                case `gd_auction_link`:
                    break;
                default:
                    $(bodyRow).append($td({}, bodyData[i][j]));
            }
        }
        var tableRowCallback = getDomainSEODataCallback(bodyRow);
        getDomainSEOData(bodyData[i]['Domain'], tableRowCallback);
    }

    // Footer
    var footerRow = $tr({});
    $(footer).append(footerRow);
    for (i in footerData) {
        $(footerRow).append($th({}, footerData[i] + ""));
    }

    // btable btablestriped btablebordered btablehovered btablecondensed btablerows
    // Put it all together
    var table = $table({
        "_class": "table table-striped table-bordered",
        "className": "table table-striped table-bordered"
    });
    $(table).append(header);
    $(table).append(footer);
    $(table).append(body);

    // Add the functionality
    tableFunctionalityCallback(table, header, body, footer, bodyData);

    // Return it
    return table;

};